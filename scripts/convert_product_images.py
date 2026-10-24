import os
import sys
from PIL import Image

def convert_to_catalog_webp(src_path: str, sku: str, output_dir: str = 'public/images/products'):
    dest_path = os.path.join(output_dir, f"{sku.lower()}.webp")
    img = Image.open(src_path)
    if img.mode != 'RGB':
        img = img.convert('RGB')
    
    w, h = img.size
    target_ratio = 800.0 / 1000.0
    current_ratio = w / h

    if current_ratio > target_ratio:
        new_w = int(h * target_ratio)
        left = (w - new_w) // 2
        img = img.crop((left, 0, left + new_w, h))
    else:
        new_h = int(w / target_ratio)
        top = (h - new_h) // 2
        img = img.crop((0, top, w, top + new_h))

    img = img.resize((800, 1000), Image.Resampling.LANCZOS)
    img.save(dest_path, 'WEBP', quality=90)
    print(f"Processed {sku} -> {dest_path} ({img.size})")

if __name__ == '__main__':
    if len(sys.argv) >= 3:
        convert_to_catalog_webp(sys.argv[1], sys.argv[2])
    else:
        print("Usage: python convert_product_images.py <src_path> <sku>")
