#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Car Rental AI Image Processor
- Self-hosted background removal using rembg (MIT License).
- Canvas normalization: 1600x1000px (8:5 aspect ratio), transparent RGBA background.
- Preserves vehicle details, color, rims, logos, perspective without regenerative hallucination.
- Output: Master WebP (1600x1000) and Thumbnail WebP (320x200).
"""

import sys
import os
import json
import argparse
from PIL import Image

def process_car_image(input_path, output_path, thumb_path):
    try:
        if not os.path.exists(input_path):
            return {
                "success": False,
                "error": f"Tệp ảnh nguồn không tồn tại: {input_path}"
            }

        # 1. Đọc ảnh gốc bằng PIL
        with Image.open(input_path) as orig_img:
            orig_w, orig_h = orig_img.size
            if orig_w < 100 or orig_h < 100:
                return {
                    "success": False,
                    "error": "Kích thước ảnh quá nhỏ, tối thiểu 100x100px."
                }
            rgba_img = orig_img.convert("RGBA")

        # 2. Xóa nền tự vận hành bằng rembg
        from rembg import remove
        no_bg = remove(rgba_img)

        # 3. Tính toán Bounding Box của xe
        bbox = no_bg.getbbox()
        if not bbox:
            return {
                "success": False,
                "error": "Không phát hiện được phương tiện trong ảnh. Vui lòng chọn ảnh khác rõ nét hơn."
            }

        left, upper, right, lower = bbox
        obj_w = right - left
        obj_h = lower - upper

        # Kiểm tra diện tích xe tối thiểu (tránh trường hợp chỉ có vài pixel rác)
        obj_area = obj_w * obj_h
        total_area = orig_w * orig_h
        if obj_area < (total_area * 0.04) or obj_w < 60 or obj_h < 40:
            return {
                "success": False,
                "error": "Đối tượng xe quá nhỏ hoặc không đủ chi tiết để chuẩn hóa."
            }

        # Cắt đúng phần xe đã tách nền
        car_cropped = no_bg.crop(bbox)

        # 4. Tạo Canvas chuẩn 1600x1000 (tỷ lệ 8:5) với nền trong suốt RGBA
        CANVAS_W = 1600
        CANVAS_H = 1000
        canvas = Image.new("RGBA", (CANVAS_W, CANVAS_H), (0, 0, 0, 0))

        # 5. Vùng an toàn chuẩn hóa:
        # Chiều rộng tối đa xe chiếm 82% canvas (1312px)
        # Chiều cao tối đa xe chiếm 74% canvas (740px)
        safe_max_w = int(CANVAS_W * 0.82)
        safe_max_h = int(CANVAS_H * 0.74)

        scale = min(safe_max_w / obj_w, safe_max_h / obj_h)
        new_w = max(1, int(obj_w * scale))
        new_h = max(1, int(obj_h * scale))

        # Resize xe giữ nguyên tỷ lệ sắc nét
        resample_filter = getattr(Image, 'Resampling', Image).LANCZOS
        car_resized = car_cropped.resize((new_w, new_h), resample=resample_filter)

        # Căn giữa theo trục ngang X
        pos_x = (CANVAS_W - new_w) // 2

        # Căn trục dọc Y: Đặt xe cân đối, hơi hạ thấp một chút để trọng tâm xe vững chãi (tiếp đất tự nhiên)
        pos_y = int((CANVAS_H - new_h) * 0.56)
        # Đảm bảo không bị cắt mép trên hoặc tràn mép dưới
        pos_y = max(30, min(pos_y, CANVAS_H - new_h - 25))

        # Dán xe vào canvas
        canvas.paste(car_resized, (pos_x, pos_y), car_resized)

        # 6. Đảm bảo thư mục lưu tồn tại
        out_dir = os.path.dirname(output_path)
        if out_dir and not os.path.exists(out_dir):
            os.makedirs(out_dir, exist_ok=True)

        thumb_dir = os.path.dirname(thumb_path)
        if thumb_dir and not os.path.exists(thumb_dir):
            os.makedirs(thumb_dir, exist_ok=True)

        # 7. Lưu ảnh WebP chất lượng cao (1600x1000px)
        canvas.save(output_path, format="WEBP", quality=92, method=6)

        # 8. Tạo và lưu thumbnail WebP (320x200px - tỷ lệ 8:5)
        thumbnail = canvas.resize((320, 200), resample=resample_filter)
        thumbnail.save(thumb_path, format="WEBP", quality=85)

        return {
            "success": True,
            "canvas_width": CANVAS_W,
            "canvas_height": CANVAS_H,
            "car_width": new_w,
            "car_height": new_h,
            "original_size": [orig_w, orig_h],
            "bbox": [left, upper, right, lower],
            "output_path": output_path,
            "thumb_path": thumb_path
        }

    except Exception as e:
        return {
            "success": False,
            "error": f"Lỗi xử lý hình ảnh: {str(e)}"
        }

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="AI Car Image Background Removal & Normalization")
    parser.add_argument("--input", required=True, help="Path to input car image")
    parser.add_argument("--output", required=True, help="Path to output normalized WebP image (1600x1000)")
    parser.add_argument("--thumb", required=True, help="Path to output thumbnail WebP image (320x200)")
    args = parser.parse_args()

    result = process_car_image(args.input, args.output, args.thumb)
    print(json.dumps(result, ensure_ascii=False))
    if not result.get("success"):
        sys.exit(1)
    sys.exit(0)
