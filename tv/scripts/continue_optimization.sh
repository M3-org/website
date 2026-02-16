#!/bin/bash
set -euo pipefail

# Simple continuation script for optimization
cd remote_assets

echo "=== Continuing Video Optimization ==="
echo ""

# Process remaining videos
for file in $(find . -name "*.mp4" -type f | sort); do
    size=$(stat -c%s "$file")
    size_mb=$((size / 1024 / 1024))

    # Skip files already optimized (< 5MB usually means already processed or very short)
    if [[ $size_mb -lt 2 ]]; then
        echo "Skipping small file: $file ($size_mb MB)"
        continue
    fi

    echo "Processing: $file ($(numfmt --to=iec-i --suffix=B $size))"

    output="${file%.mp4}_optimized.mp4"

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
        "$output" 2>&1 | grep -E "(frame=)" | tail -1 || true

    if [[ -f "$output" ]]; then
        new_size=$(stat -c%s "$output")
        echo "  Original: $(numfmt --to=iec-i --suffix=B $size)"
        echo "  New: $(numfmt --to=iec-i --suffix=B $new_size)"
        reduction=$(( 100 - (new_size * 100 / size) ))
        echo "  Reduction: ${reduction}%"
        mv "$output" "$file"
    fi
    echo ""
done

echo "=== Processing Audio Files ==="
echo ""

for file in $(find . -name "*.mp3" -type f | sort); do
    size=$(stat -c%s "$file")
    echo "Processing: $file ($(numfmt --to=iec-i --suffix=B $size))"

    output="${file%.mp3}_optimized.mp3"

    ffmpeg -i "$file" \
        -codec:a libmp3lame \
        -b:a 128k \
        -ar 44100 \
        -y \
        "$output" 2>&1 | grep -E "(size=)" | tail -1 || true

    if [[ -f "$output" ]]; then
        new_size=$(stat -c%s "$output")
        echo "  Original: $(numfmt --to=iec-i --suffix=B $size)"
        echo "  New: $(numfmt --to=iec-i --suffix=B $new_size)"
        reduction=$(( 100 - (new_size * 100 / size) ))
        echo "  Reduction: ${reduction}%"
        mv "$output" "$file"
    fi
    echo ""
done

echo "=== Processing Images ==="
echo ""

for file in $(find . -name "*.png" -type f); do
    size=$(stat -c%s "$file")
    echo "Processing PNG: $file ($(numfmt --to=iec-i --suffix=B $size))"

    temp="${file%.png}_temp.png"
    pngquant --quality=80-95 --speed 1 --force --output "$temp" "$file" 2>&1 || true

    if [[ -f "$temp" ]]; then
        new_size=$(stat -c%s "$temp")
        echo "  Original: $(numfmt --to=iec-i --suffix=B $size)"
        echo "  New: $(numfmt --to=iec-i --suffix=B $new_size)"
        reduction=$(( 100 - (new_size * 100 / size) ))
        echo "  Reduction: ${reduction}%"
        mv "$temp" "$file"
    fi
    echo ""
done

for file in $(find . -name "*.jpg" -o -name "*.jpeg" -type f); do
    size=$(stat -c%s "$file")
    echo "Processing JPEG: $file ($(numfmt --to=iec-i --suffix=B $size))"

    # Check dimensions
    dimensions=$(ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 "$file" 2>/dev/null)
    width=$(echo "$dimensions" | cut -d'x' -f1)
    height=$(echo "$dimensions" | cut -d'x' -f2)

    if [[ $width -gt 2048 || $height -gt 2048 ]]; then
        echo "  Resizing from ${width}x${height} to max 2048x2048"
        temp="${file%.jpg}_resized.jpg"
        ffmpeg -i "$file" -vf "scale='min(2048,iw)':'min(2048,ih)':force_original_aspect_ratio=decrease" -q:v 85 -y "$temp" 2>&1 | grep -E "(frame=)" | tail -1 || true
        mv "$temp" "$file"
    fi

    jpegoptim --max=85 --strip-all "$file" 2>&1 || true

    new_size=$(stat -c%s "$file")
    echo "  Original: $(numfmt --to=iec-i --suffix=B $size)"
    echo "  New: $(numfmt --to=iec-i --suffix=B $new_size)"
    reduction=$(( 100 - (new_size * 100 / size) ))
    echo "  Reduction: ${reduction}%"
    echo ""
done

cd ..
echo "=== Optimization Complete ==="
du -sh remote_assets/
