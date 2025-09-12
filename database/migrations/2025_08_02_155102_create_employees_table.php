<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('emplyeetype')->nullable();
            $table->string('eid')->unique();
            $table->string('fname');
            $table->string('lname');
            $table->string('nationalid')->nullable();
            $table->dateTime('dob');
            $table->string('level')->nullable();
            $table->string('meritalstatus')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('emergencycontactname')->nullable();
            $table->string('emergencycontactphone')->nullable();
            $table->string('address')->nullable();
            $table->string('designation')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('joindate')->nullable();
            $table->string('probitionprioed')->nullable();
            $table->string('reportingmanager')->nullable();
            $table->string('workshift')->nullable();
            $table->decimal('salary', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
