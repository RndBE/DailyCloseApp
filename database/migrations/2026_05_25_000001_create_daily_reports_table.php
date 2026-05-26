<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('report_date');
            $table->boolean('overtime_status')->default(false);
            $table->time('overtime_start')->nullable();
            $table->time('overtime_end')->nullable();
            $table->text('completed_work');
            $table->text('unfinished_work')->nullable();
            $table->text('obstacles')->nullable();
            $table->boolean('need_leader_help')->default(false);
            $table->text('leader_help_description')->nullable();
            $table->text('tomorrow_plan');
            $table->time('work_finished_at');
            $table->text('additional_notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'report_date'], 'unique_user_report_date');
            $table->index('report_date');
            $table->index('overtime_status');
            $table->index('need_leader_help');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
