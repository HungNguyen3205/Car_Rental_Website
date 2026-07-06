FROM python:3.10-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements
COPY requirements.txt .

# Install dependencies (using CPU version of PyTorch to minimize image size)
RUN pip install --no-cache-dir -r requirements.txt
RUN pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu
RUN pip install --no-cache-dir nltk

# Pre-download NLTK data
RUN python -m nltk.downloader punkt

COPY . .

EXPOSE 5005

CMD ["python", "main.py"]
