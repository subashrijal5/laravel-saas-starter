<?php

use App\Http\Controllers\Organization\AcceptInvitationController;
use App\Http\Controllers\Organization\LeaveOrganizationController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationInvitationController;
use App\Http\Controllers\Organization\OrganizationMemberController;
use App\Http\Controllers\Organization\SwitchOrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');

    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::post('organizations/switch/{organization}', SwitchOrganizationController::class)->name('organizations.switch');
    Route::post('organizations/{organization}/leave', LeaveOrganizationController::class)->name('organizations.leave');

    Route::get('organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
    Route::patch('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update');
    Route::delete('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy');

    Route::post('organizations/{organization}/invitations', [OrganizationInvitationController::class, 'store'])->name('organizations.invitations.store');
    Route::patch('organizations/invitations/{invitation}', [OrganizationInvitationController::class, 'update'])->name('organizations.invitations.resend');
    Route::delete('organizations/invitations/{invitation}', [OrganizationInvitationController::class, 'destroy'])->name('organizations.invitations.destroy');

    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
});
