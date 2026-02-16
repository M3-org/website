#!/bin/bash
set -euo pipefail

ASSETS_DIR="remote_assets"
BACKUP_DIR="${ASSETS_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
DRY_RUN="${DRY_RUN:-false}"

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Media Optimization Script ===${NC}"
echo "Assets directory: $ASSETS_DIR"
echo "Dry run: $DRY_RUN"
echo ""

# Backup original files
if [[ "$DRY_RUN" != "true" ]]; then
  echo -e "${YELLOW}Creating backup: $BACKUP_DIR${NC}"
  cp -r "$ASSETS_DIR" "$BACKUP_DIR"
  echo -e "${GREEN}Backup created successfully${NC}"
  echo ""
fi

# Process videos
echo -e "${GREEN}=== Processing Videos (.mp4) ===${NC}"
video_count=0
while IFS= read -r file; do
  video_count=$((video_count + 1))
  original_size=$(stat -c%s "$file" 2>/dev/null || echo "0")
  if [[ "$original_size" == "0" ]]; then
    echo -e "${RED}Skipping missing file: $file${NC}"
    continue
  fi
  echo -e "${YELLOW}Optimizing video [$video_count]: $(basename "$file")${NC}"
  echo "  Original size: $(numfmt --to=iec-i --suffix=B $original_size)"

  output="${file%.mp4}_optimized.mp4"

  if [[ "$DRY_RUN" == "true" ]]; then
    echo -e "${YELLOW}  [DRY RUN] Would optimize this file${NC}"
    continue
  fi

  ffmpeg -i "$file" \
    -c:v libx264 \
    -preset slow \
    -crf 28 \
    -maxrate 4M \
    -bufsize 8M \
    -vf "format=yuv420p" \
    -c:a aac \
    -b:a 128k \
    -movflags +faststart \
    -y \
    "$output" 2>&1 | grep -E "(Duration|time=|size=)" || true

  if [[ -f "$output" ]]; then
    new_size=$(stat -c%s "$output")
    echo "  New size: $(numfmt --to=iec-i --suffix=B $new_size)"
    reduction=$(( 100 - (new_size * 100 / original_size) ))
    echo -e "  ${GREEN}Reduction: ${reduction}%${NC}"
    mv "$output" "$file"
  else
    echo -e "${RED}  ERROR: Failed to create optimized file${NC}"
  fi
  echo ""
done

# Process audio
echo -e "${GREEN}=== Processing Audio (.mp3) ===${NC}"
audio_count=0
find "$ASSETS_DIR" -name "*.mp3" -type f | while read -r file; do
  audio_count=$((audio_count + 1))
  original_size=$(stat -c%s "$file")
  echo -e "${YELLOW}Optimizing audio [$audio_count]: $(basename "$file")${NC}"
  echo "  Original size: $(numfmt --to=iec-i --suffix=B $original_size)"

  output="${file%.mp3}_optimized.mp3"

  if [[ "$DRY_RUN" == "true" ]]; then
    echo -e "${YELLOW}  [DRY RUN] Would optimize this file${NC}"
    continue
  fi

  ffmpeg -i "$file" \
    -codec:a libmp3lame \
    -b:a 128k \
    -ar 44100 \
    -y \
    "$output" 2>&1 | grep -E "(Duration|time=|size=)" || true

  if [[ -f "$output" ]]; then
    new_size=$(stat -c%s "$output")
    echo "  New size: $(numfmt --to=iec-i --suffix=B $new_size)"
    reduction=$(( 100 - (new_size * 100 / original_size) ))
    echo -e "  ${GREEN}Reduction: ${reduction}%${NC}"
    mv "$output" "$file"
  else
    echo -e "${RED}  ERROR: Failed to create optimized file${NC}"
  fi
  echo ""
done

# Process PNG
echo -e "${GREEN}=== Processing PNG Images ===${NC}"
png_count=0
find "$ASSETS_DIR" -name "*.png" -type f | while read -r file; do
  png_count=$((png_count + 1))
  original_size=$(stat -c%s "$file")
  echo -e "${YELLOW}Optimizing PNG [$png_count]: $(basename "$file")${NC}"
  echo "  Original size: $(numfmt --to=iec-i --suffix=B $original_size)"

  if [[ "$DRY_RUN" == "true" ]]; then
    echo -e "${YELLOW}  [DRY RUN] Would optimize this file${NC}"
    continue
  fi

  temp_file="${file%.png}_temp.png"
  pngquant --quality=80-95 --speed 1 --force --output "$temp_file" "$file" || true

  if [[ -f "$temp_file" ]]; then
    new_size=$(stat -c%s "$temp_file")
    echo "  New size: $(numfmt --to=iec-i --suffix=B $new_size)"
    reduction=$(( 100 - (new_size * 100 / original_size) ))
    echo -e "  ${GREEN}Reduction: ${reduction}%${NC}"
    mv "$temp_file" "$file"
  else
    echo -e "${RED}  WARNING: pngquant failed, keeping original${NC}"
  fi
  echo ""
done

# Process JPEG
echo -e "${GREEN}=== Processing JPEG Images ===${NC}"
jpeg_count=0
find "$ASSETS_DIR" \( -name "*.jpg" -o -name "*.jpeg" \) -type f | while read -r file; do
  jpeg_count=$((jpeg_count + 1))
  original_size=$(stat -c%s "$file")
  echo -e "${YELLOW}Optimizing JPEG [$jpeg_count]: $(basename "$file")${NC}"
  echo "  Original size: $(numfmt --to=iec-i --suffix=B $original_size)"

  if [[ "$DRY_RUN" == "true" ]]; then
    echo -e "${YELLOW}  [DRY RUN] Would optimize this file${NC}"
    continue
  fi

  # Check if image is larger than 2048x2048 and resize if needed
  dimensions=$(ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 "$file")
  width=$(echo "$dimensions" | cut -d'x' -f1)
  height=$(echo "$dimensions" | cut -d'x' -f2)

  if [[ $width -gt 2048 || $height -gt 2048 ]]; then
    echo "  Resizing from ${width}x${height} to max 2048x2048"
    temp_file="${file%.jpg}_resized.jpg"
    ffmpeg -i "$file" -vf "scale='min(2048,iw)':'min(2048,ih)':force_original_aspect_ratio=decrease" -q:v 85 -y "$temp_file" 2>&1 | grep -E "(Duration|time=)" || true
    mv "$temp_file" "$file"
  fi

  jpegoptim --max=85 --strip-all "$file"

  new_size=$(stat -c%s "$file")
  echo "  New size: $(numfmt --to=iec-i --suffix=B $new_size)"
  reduction=$(( 100 - (new_size * 100 / original_size) ))
  echo -e "  ${GREEN}Reduction: ${reduction}%${NC}"
  echo ""
done

echo -e "${GREEN}=== Optimization Complete! ===${NC}"
echo ""
echo "Final directory size:"
du -sh "$ASSETS_DIR"
echo ""
echo -e "${GREEN}Backup location: $BACKUP_DIR${NC}"
