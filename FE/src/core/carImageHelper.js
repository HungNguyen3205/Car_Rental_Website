export const CAR_IMAGE_MAP = {
  'xpander': '/assets/images/cars/xpander.png',
  'camry': '/assets/images/cars/camry.png',
  'audi a6': '/assets/images/cars/audi_a6.png',
  'mercedes': '/assets/images/cars/mercedes_c200.png',
  'santafe': '/assets/images/cars/santafe.png',
  'mazda cx-5': '/assets/images/cars/cx5.png',
  'cx5': '/assets/images/cars/cx5.png',
  'vf8': '/assets/images/cars/vf8.png',
  'vf7': '/assets/images/cars/vf7.png',
  'vf9': '/assets/images/vf9.png',
  'vf 7': '/assets/images/cars/vf7.png',
  'vf 8': '/assets/images/cars/vf8.png',
  'vf 9': '/assets/images/vf9.png'
};

export const DEFAULT_CAR_IMAGE = '/assets/images/vf9.png';

/**
 * Standardize car image resolution:
 * 1. Prioritize real API field: image or hinh_anh
 * 2. Support absolute URL (http://, https://, data:) or local path (/...)
 * 3. Fallback to model name mapping ONLY if no image field was provided
 * 4. Fallback to default image if missing
 */
export const resolveCarImage = (car) => {
  const raw = car?.image || car?.hinh_anh;
  if (raw && typeof raw === 'string' && raw.trim() !== '') {
    const trimmed = raw.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:')) {
      return trimmed;
    }
    if (trimmed.startsWith('/')) {
      return trimmed;
    }
    if (trimmed.startsWith('assets/')) {
      return `/${trimmed}`;
    }
    return `/assets/images/${trimmed}`;
  }

  const modelName = (car?.name || car?.ten_xe || '').toLowerCase();
  for (const [key, path] of Object.entries(CAR_IMAGE_MAP)) {
    if (modelName.includes(key)) {
      return path;
    }
  }

  return DEFAULT_CAR_IMAGE;
};

/**
 * Safe image error handler to prevent infinite loop
 */
export const handleCarImgError = (event, fallback = DEFAULT_CAR_IMAGE) => {
  if (event && event.target) {
    if (event.target.dataset.hasFailed) return;
    event.target.dataset.hasFailed = 'true';
    event.target.src = fallback;
  }
};
