#!/usr/bin/env python3
"""
Catalog images by folder with shared context.

Analyzes images grouped by folder, sharing context between related images
to generate succinct, consistent descriptions.

Usage:
    # Analyze a specific folder
    python catalog.py resources/m3tv/M3org/Environments/ClankTank

    # Analyze and update manifest.json
    python catalog.py resources/m3tv/M3org --manifest resources/m3tv/manifest.json

    # Dry run (print descriptions without saving)
    python catalog.py resources/m3tv/M3org/stills --dry-run
"""

import os
import sys
import json
import base64
import argparse
from pathlib import Path
from typing import Optional

import requests
from dotenv import load_dotenv

# Load .env
load_dotenv(Path(__file__).parent / ".env")
load_dotenv()

OPENROUTER_API_KEY = os.environ.get("OPENROUTER_API_KEY")
OPENROUTER_ENDPOINT = "https://openrouter.ai/api/v1/chat/completions"
VISION_MODEL = "google/gemini-2.5-flash-image-preview"

IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".gif", ".webp"}


def load_image_base64(path: Path) -> str:
    """Load image as base64 data URL."""
    data = path.read_bytes()
    suffix = path.suffix.lower()
    mime_types = {
        ".png": "image/png",
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".gif": "image/gif",
        ".webp": "image/webp",
    }
    mime = mime_types.get(suffix, "image/png")
    b64 = base64.b64encode(data).decode("utf-8")
    return f"data:{mime};base64,{b64}"


def analyze_images(images: list[Path], context: str, batch_size: int = 4) -> dict[str, str]:
    """
    Analyze multiple images with shared context.
    Returns dict mapping filename to description.
    """
    if not images:
        return {}

    # Build content with all images
    content = []

    # Add images (limit batch size to avoid token limits)
    batch = images[:batch_size]
    for img_path in batch:
        try:
            img_url = load_image_base64(img_path)
            content.append({
                "type": "image_url",
                "image_url": {"url": img_url}
            })
        except Exception as e:
            print(f"  Warning: Could not load {img_path.name}: {e}", file=sys.stderr)
            continue

    if not content:
        return {}

    # Build filenames list for reference
    filenames = [img.name for img in batch]

    prompt = f"""Context: {context}

Analyze these {len(filenames)} images and provide a succinct description for each.

Files: {', '.join(filenames)}

For each image, write ONE short description (10-20 words max) that captures:
- What is shown (subject, scene, action)
- Visual style or notable features
- How it relates to the context

Respond in JSON format:
{{
  "filename1.jpg": "description",
  "filename2.png": "description"
}}

Be specific but brief. No fluff words."""

    content.append({"type": "text", "text": prompt})

    try:
        response = requests.post(
            OPENROUTER_ENDPOINT,
            headers={
                "Authorization": f"Bearer {OPENROUTER_API_KEY}",
                "Content-Type": "application/json",
            },
            json={
                "model": VISION_MODEL,
                "messages": [{"role": "user", "content": content}],
            },
            timeout=120,
        )
        response.raise_for_status()

        result = response.json()
        text = result["choices"][0]["message"]["content"].strip()

        # Parse JSON from response (handle markdown code blocks)
        if "```json" in text:
            text = text.split("```json")[1].split("```")[0]
        elif "```" in text:
            text = text.split("```")[1].split("```")[0]

        return json.loads(text)

    except json.JSONDecodeError as e:
        print(f"  Warning: Could not parse JSON response: {e}", file=sys.stderr)
        return {}
    except requests.RequestException as e:
        print(f"  API error: {e}", file=sys.stderr)
        if hasattr(e, 'response') and e.response is not None:
            print(f"  Response: {e.response.text[:500]}", file=sys.stderr)
        return {}


def get_folder_context(folder: Path) -> str:
    """Generate context string from folder path."""
    parts = folder.parts

    # Find relevant parts (after M3org or similar)
    context_parts = []
    capture = False
    for part in parts:
        if part in ("M3org", "m3tv", "resources"):
            capture = True
            continue
        if capture and part not in (".", ".."):
            context_parts.append(part)

    if not context_parts:
        context_parts = [folder.name]

    # Build context description
    folder_name = " / ".join(context_parts)

    # Add semantic hints based on folder names
    hints = []
    folder_lower = folder_name.lower()
    if "environment" in folder_lower:
        hints.append("3D environment renders")
    if "character" in folder_lower:
        hints.append("character artwork")
    if "clank" in folder_lower:
        hints.append("Clank Tank investment show")
    if "jedai" in folder_lower or "council" in folder_lower:
        hints.append("JedAI Council debate show")
    if "bazaar" in folder_lower:
        hints.append("Eliza Agent Bazaar")
    if "stills" in folder_lower:
        hints.append("promotional stills")
    if "thumbnail" in folder_lower:
        hints.append("transparent PNG character cutouts/mascots")
    if "rebrand" in folder_lower:
        hints.append("rebranding assets")
    if "stonk" in folder_lower or "stock" in folder_lower:
        hints.append("StonkWars trading show")

    context = f"M3TV media assets - {folder_name}"
    if hints:
        context += f" ({', '.join(hints)})"

    return context


def process_folder(folder: Path, base_path: Path = None, dry_run: bool = False) -> dict[str, str]:
    """Process all images in a folder and return descriptions.

    Returns dict mapping relative_path -> description for uniqueness across folders.
    """
    images = sorted([
        f for f in folder.iterdir()
        if f.is_file() and f.suffix.lower() in IMAGE_EXTENSIONS
    ])

    if not images:
        return {}

    context = get_folder_context(folder)
    print(f"\n[{folder.name}] {len(images)} images")
    print(f"  Context: {context}")

    all_descriptions = {}

    # Process in batches
    batch_size = 4
    for i in range(0, len(images), batch_size):
        batch = images[i:i + batch_size]
        print(f"  Analyzing batch {i//batch_size + 1}/{(len(images) + batch_size - 1)//batch_size}...")

        descriptions = analyze_images(batch, context, batch_size)

        # Map filename back to relative path for uniqueness
        for filename, desc in descriptions.items():
            img_path = folder / filename
            if base_path:
                rel_path = str(img_path.relative_to(base_path))
            else:
                rel_path = filename
            all_descriptions[rel_path] = desc
            print(f"    {filename}: {desc[:60]}...")

    return all_descriptions


def update_manifest(manifest_path: Path, descriptions: dict[str, str]) -> int:
    """Update manifest.json with descriptions. Returns count of updated entries."""
    with open(manifest_path) as f:
        manifest = json.load(f)

    updated = 0
    for entry in manifest.get("files", []):
        # Match on path (relative) field
        rel_path = entry.get("path", "")
        # Also try matching by filename as fallback
        filename = entry.get("name", "")

        if rel_path in descriptions:
            entry["description"] = descriptions[rel_path]
            updated += 1
        elif filename in descriptions:
            entry["description"] = descriptions[filename]
            updated += 1

    with open(manifest_path, "w") as f:
        json.dump(manifest, f, indent=2)

    return updated


def main():
    parser = argparse.ArgumentParser(
        description="Catalog images by folder with shared context",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__
    )
    parser.add_argument("folder", type=Path, help="Folder to analyze")
    parser.add_argument(
        "-m", "--manifest",
        type=Path,
        help="Update manifest.json with descriptions"
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Print descriptions without saving"
    )
    parser.add_argument(
        "-r", "--recursive",
        action="store_true",
        help="Process subfolders recursively"
    )
    parser.add_argument(
        "-o", "--output",
        type=Path,
        help="Output JSON file for descriptions"
    )

    args = parser.parse_args()

    if not OPENROUTER_API_KEY:
        print("Error: OPENROUTER_API_KEY not set", file=sys.stderr)
        return 1

    if not args.folder.exists():
        print(f"Error: Folder not found: {args.folder}", file=sys.stderr)
        return 1

    all_descriptions = {}

    if args.recursive:
        # Process all subfolders
        folders = sorted([args.folder] + [
            d for d in args.folder.rglob("*") if d.is_dir()
        ])
        base_path = args.folder
    else:
        folders = [args.folder]
        base_path = args.folder

    for folder in folders:
        descriptions = process_folder(folder, base_path, args.dry_run)
        all_descriptions.update(descriptions)

    print(f"\nTotal: {len(all_descriptions)} images cataloged")

    # Save outputs
    if args.output and not args.dry_run:
        with open(args.output, "w") as f:
            json.dump(all_descriptions, f, indent=2)
        print(f"Saved to: {args.output}")

    if args.manifest and not args.dry_run:
        updated = update_manifest(args.manifest, all_descriptions)
        print(f"Updated {updated} entries in {args.manifest}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
