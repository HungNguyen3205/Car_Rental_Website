<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm các cột phân công, người cập nhật, người hoàn tất và liên kết xe quan tâm vào bảng contacts
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->after('handled_at')->index();
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            $table->unsignedBigInteger('updated_by')->nullable()->after('assigned_at')->index();
            $table->unsignedBigInteger('completed_by')->nullable()->after('updated_by')->index();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
            $table->unsignedBigInteger('car_id')->nullable()->after('user_id')->index();

            $table->foreign('assigned_to')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('set null');
        });

        // 2. Tạo bảng contact_activities để lưu trữ nhiều bản ghi lịch sử xử lý, phân công và ghi chú nội bộ
        Schema::create('contact_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name', 100)->default('Hệ thống');
            $table->string('type', 50)->default('note')->index()->comment('note, status_change, assigned, reassigned');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->index(['contact_id', 'created_at']);
        });

        // 3. Bảo toàn dữ liệu cũ (Data Migration):
        // Di chuyển ghi chú hiện tại (ghi_chu_noi_bo) và người xử lý cũ (handled_by) sang các cột và bảng mới
        $existingContacts = DB::table('contacts')->get();
        foreach ($existingContacts as $c) {
            $handlerId = $c->handled_by;
            $handlerName = 'Hệ thống';

            if ($handlerId) {
                $admin = DB::table('admins')->where('id', $handlerId)->first();
                if ($admin) {
                    $handlerName = $admin->ho_ten ?? 'Nhân viên #' . $handlerId;
                }
            }

            // Đồng bộ sang assigned_to, updated_by và completed_by nếu đã có handled_by
            $updateData = [];
            if ($handlerId) {
                $updateData['assigned_to'] = $handlerId;
                $updateData['assigned_at'] = $c->handled_at ?? $c->updated_at ?? now();
                $updateData['updated_by'] = $handlerId;

                if ((int) $c->trang_thai === 2) { // RESOLVED
                    $updateData['completed_by'] = $handlerId;
                    $updateData['completed_at'] = $c->handled_at ?? $c->updated_at ?? now();
                }
            }

            if (!empty($updateData)) {
                DB::table('contacts')->where('id', $c->id)->update($updateData);
            }

            // Nếu có ghi chú nội bộ cũ, thêm vào lịch sử contact_activities
            if (!empty(trim((string) $c->ghi_chu_noi_bo))) {
                DB::table('contact_activities')->insert([
                    'contact_id' => $c->id,
                    'admin_id'   => $handlerId,
                    'admin_name' => $handlerName,
                    'type'       => 'note',
                    'content'    => trim($c->ghi_chu_noi_bo),
                    'metadata'   => json_encode(['migrated' => true, 'source' => 'ghi_chu_noi_bo']),
                    'created_at' => $c->handled_at ?? $c->updated_at ?? now(),
                    'updated_at' => $c->handled_at ?? $c->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_activities');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['car_id']);

            $table->dropColumn([
                'assigned_to',
                'assigned_at',
                'updated_by',
                'completed_by',
                'completed_at',
                'car_id',
            ]);
        });
    }
};
