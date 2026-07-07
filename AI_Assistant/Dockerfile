FROM python:3.10-slim

WORKDIR /app

# Khởi tạo các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y \
    curl \
    gcc \
    && rm -rf /var/lib/apt/lists/*

# Copy file định nghĩa thư viện
COPY requirements.txt .

RUN pip install --no-cache-dir --default-timeout=1000 torch --index-url https://download.pytorch.org/whl/cpu

RUN pip install --no-cache-dir --default-timeout=1000 -r requirements.txt
RUN pip install --no-cache-dir nltk

RUN python -m nltk.downloader punkt punkt_tab

COPY . .

EXPOSE 5005

CMD ["python", "main.py"]