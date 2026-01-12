#!/usr/bin/env python3
"""
Simple image generation with optional reference image.

Usage:
    # Text-only generation
    python scripts/posters/imgen.py "a cute robot mascot" -o robot.png

    # With reference image
    python scripts/posters/imgen.py "same character but celebrating" -i ref.png -o output.png

    # Custom aspect ratio
    python scripts/posters/imgen.py "landscape scene" -o scene.png --aspect 16:9
"""

import os
import sys
import base64
import argparse
from pathlib import Path

import requests
from dotenv import load_dotenv

# Load .env from script directory or current directory
load_dotenv(Path(__file__).parent / ".env")
load_dotenv()

OPENROUTER_API_KEY = os.environ.get("OPENROUTER_API_KEY")
OPENROUTER_ENDPOINT = "https://openrouter.ai/api/v1/chat/completions"
IMAGE_MODEL = "google/gemini-3-pro-image-preview"


def load_image_as_base64(path: Path) -> str:
    """Load image and convert to base64."""
    with open(path, "rb") as f:
        return base64.b64encode(f.read()).decode("utf-8")


def generate(prompt: str, image_path: Path = None, aspect: str = "1:1") -> bytes:
    """Generate image, optionally with reference."""
    content = []

    # Add reference image if provided
    if image_path:
        img_b64 = load_image_as_base64(image_path)
        ext = image_path.suffix.lower().lstrip(".")
        mime = {"jpg": "jpeg", "jpeg": "jpeg", "png": "png", "webp": "webp"}.get(ext, "png")
        content.append({
            "type": "image_url",
            "image_url": {"url": f"data:image/{mime};base64,{img_b64}"}
        })

    content.append({"type": "text", "text": prompt})

    resp = requests.post(
        OPENROUTER_ENDPOINT,
        headers={
            "Authorization": f"Bearer {OPENROUTER_API_KEY}",
            "Content-Type": "application/json",
        },
        json={
            "model": IMAGE_MODEL,
            "modalities": ["image", "text"],
            "messages": [{"role": "user", "content": content}],
            "image_config": {
                "aspect_ratio": aspect,
            },
        },
        timeout=180,
    )
    resp.raise_for_status()
    result = resp.json()

    # Extract image from response
    images = result["choices"][0]["message"].get("images", [])
    if not images:
        raise ValueError("No image generated")

    image_url = images[0]["image_url"]["url"]
    if image_url.startswith("data:"):
        base64_data = image_url.split(",", 1)[1]
        return base64.b64decode(base64_data)

    raise ValueError("Unexpected image format")


def main():
    parser = argparse.ArgumentParser(description="Generate image with optional reference")
    parser.add_argument("prompt", help="Generation prompt")
    parser.add_argument("-i", "--image", type=Path, help="Reference image")
    parser.add_argument("-o", "--output", type=Path, required=True, help="Output file")
    parser.add_argument("--aspect", default="1:1", help="Aspect ratio (default: 1:1)")

    args = parser.parse_args()

    if not OPENROUTER_API_KEY:
        print("Error: OPENROUTER_API_KEY not set", file=sys.stderr)
        sys.exit(1)

    if args.image and not args.image.exists():
        print(f"Error: Image not found: {args.image}", file=sys.stderr)
        sys.exit(1)

    print(f"Generating: {args.prompt[:50]}...")
    if args.image:
        print(f"Reference: {args.image}")

    image_bytes = generate(args.prompt, args.image, args.aspect)

    args.output.parent.mkdir(parents=True, exist_ok=True)
    with open(args.output, "wb") as f:
        f.write(image_bytes)

    print(f"Saved: {args.output}")


if __name__ == "__main__":
    main()
