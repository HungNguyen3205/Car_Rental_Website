<?php

namespace App\Services\Location;

class LocationService
{
    /**
     * Bảng danh mục địa điểm phục vụ chuẩn hóa toàn hệ thống.
     * Ánh xạ thống nhất giữa:
     * - slug URL/hệ thống nội bộ (sg, hn, dn, nt...)
     * - mã tỉnh thành mở API (79, 1, 48, 56...)
     * - tên hiển thị tiếng Việt có dấu và không dấu
     */
    public const LOCATIONS = [
        'sg' => [
            'slug'       => 'sg',
            'code'       => '79',
            'name'       => 'TP. Hồ Chí Minh',
            'short_name' => 'TP. HCM',
            'aliases'    => [
                'sg', '79', 'hcm', 'tp hcm', 'tp. hcm', 'tp.hcm',
                'ho chi minh', 'tp ho chi minh', 'tp. ho chi minh', 'thanh pho ho chi minh',
                'sai gon', 'saigon'
            ],
        ],
        'hn' => [
            'slug'       => 'hn',
            'code'       => '1',
            'name'       => 'Hà Nội',
            'short_name' => 'Hà Nội',
            'aliases'    => [
                'hn', '1', '01', 'ha noi', 'tp ha noi', 'tp. ha noi', 'thanh pho ha noi'
            ],
        ],
        'dn' => [
            'slug'       => 'dn',
            'code'       => '48',
            'name'       => 'Đà Nẵng',
            'short_name' => 'Đà Nẵng',
            'aliases'    => [
                'dn', '48', 'da nang', 'tp da nang', 'tp. da nang', 'thanh pho da nang'
            ],
        ],
        'nt' => [
            'slug'       => 'nt',
            'code'       => '56',
            'name'       => 'Nha Trang (Khánh Hòa)',
            'short_name' => 'Nha Trang',
            'aliases'    => [
                'nt', '56', 'nha trang', 'khanh hoa', 'tinh khanh hoa'
            ],
        ],
        'hp' => [
            'slug'       => 'hp',
            'code'       => '31',
            'name'       => 'Hải Phòng',
            'short_name' => 'Hải Phòng',
            'aliases'    => [
                'hp', '31', 'hai phong', 'tp hai phong', 'thanh pho hai phong'
            ],
        ],
        'ct' => [
            'slug'       => 'ct',
            'code'       => '92',
            'name'       => 'Cần Thơ',
            'short_name' => 'Cần Thơ',
            'aliases'    => [
                'ct', '92', 'can tho', 'tp can tho', 'thanh pho can tho'
            ],
        ],
        'dl' => [
            'slug'       => 'dl',
            'code'       => '68',
            'name'       => 'Đà Lạt (Lâm Đồng)',
            'short_name' => 'Đà Lạt',
            'aliases'    => [
                'dl', '68', 'da lat', 'lam dong', 'tinh lam dong'
            ],
        ],
        'vt' => [
            'slug'       => 'vt',
            'code'       => '77',
            'name'       => 'Vũng Tàu',
            'short_name' => 'Vũng Tàu',
            'aliases'    => [
                'vt', '77', 'vung tau', 'ba ria', 'ba ria vung tau'
            ],
        ],
        'bd' => [
            'slug'       => 'bd',
            'code'       => '74',
            'name'       => 'Bình Dương',
            'short_name' => 'Bình Dương',
            'aliases'    => [
                'bd', '74', 'binh duong', 'tinh binh duong'
            ],
        ],
        'dnai' => [
            'slug'       => 'dnai',
            'code'       => '75',
            'name'       => 'Đồng Nai',
            'short_name' => 'Đồng Nai',
            'aliases'    => [
                'dnai', '75', 'dong nai', 'tinh dong nai', 'bien hoa'
            ],
        ],
        'all' => [
            'slug'       => 'all',
            'code'       => 'all',
            'name'       => 'Toàn quốc',
            'short_name' => 'Toàn quốc',
            'aliases'    => [
                'all', 'toan quoc', 'tat ca'
            ],
        ],
    ];

    /**
     * Chuẩn hóa văn bản tiếng Việt (loại bỏ dấu và chuyển chữ thường).
     */
    public static function normalizeString(?string $str): string
    {
        if (!$str) {
            return '';
        }

        $str = mb_strtolower(trim($str), 'UTF-8');
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/u', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/u', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/u', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/u', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/u', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/u', 'y', $str);
        $str = preg_replace('/(đ)/u', 'd', $str);

        // Bỏ ký tự thừa
        $str = preg_replace('/[^a-z0-9]/', ' ', $str);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * Xác định slug chuẩn từ bất kỳ đầu vào nào (slug, mã code số, hoặc tên thành phố).
     */
    public static function resolveSlug(?string $input): ?string
    {
        if (!$input) {
            return null;
        }

        $raw = trim($input);
        $normalized = self::normalizeString($raw);

        // Kiểm tra trực tiếp theo slug key
        if (isset(self::LOCATIONS[$raw])) {
            return $raw;
        }

        foreach (self::LOCATIONS as $slug => $info) {
            if ($raw === $info['code'] || $raw === $info['slug']) {
                return $slug;
            }

            foreach ($info['aliases'] as $alias) {
                if ($normalized === $alias || str_contains($normalized, $alias) || str_contains($alias, $normalized)) {
                    return $slug;
                }
            }
        }

        return $raw; // Fallback lại chính input nếu không nhận dạng được
    }

    /**
     * Lấy danh sách các giá trị khả dĩ để query trong database,
     * đảm bảo không bao giờ bỏ sót xe dù bản ghi lưu slug hay lưu tên đầy đủ.
     */
    public static function getSearchCandidates(?string $input): array
    {
        if (!$input || $input === 'all') {
            return [];
        }

        $slug = self::resolveSlug($input);
        $candidates = [$input];

        if ($slug && isset(self::LOCATIONS[$slug])) {
            $info = self::LOCATIONS[$slug];
            $candidates[] = $slug;
            $candidates[] = $info['code'];
            $candidates[] = $info['name'];
            $candidates[] = $info['short_name'];
            foreach ($info['aliases'] as $alias) {
                $candidates[] = $alias;
            }
        }

        return array_unique(array_filter($candidates));
    }

    /**
     * Áp dụng điều kiện lọc địa bàn an toàn vào Eloquent Query.
     */
    public static function applyCityFilter($query, ?string $cityInput, string $column = 'cars.city')
    {
        if (!$cityInput || $cityInput === 'all' || $cityInput === '') {
            return $query;
        }

        $slug = self::resolveSlug($cityInput);
        $candidates = self::getSearchCandidates($cityInput);

        return $query->where(function ($q) use ($column, $slug, $candidates, $cityInput) {
            // 1. Khớp theo slug chuẩn
            if ($slug) {
                $q->where($column, $slug);
            }

            // 2. Khớp theo danh sách ứng viên (slug, mã tỉnh, tên có dấu, tên không dấu)
            foreach ($candidates as $cand) {
                $q->orWhere($column, 'like', '%' . $cand . '%');
            }

            // 3. Fallback theo từ khóa người dùng nhập
            $q->orWhere($column, 'like', '%' . $cityInput . '%');
        });
    }

    /**
     * Lấy danh sách địa điểm phục vụ hiển thị cho UI/API.
     */
    public static function getAllLocations(): array
    {
        return array_values(self::LOCATIONS);
    }
}
