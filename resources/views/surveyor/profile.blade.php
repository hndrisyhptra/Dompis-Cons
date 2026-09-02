@extends('layouts.surveyor')

@section('title', 'Profil')

@section('content')
<div class="px-5 pt-6 pb-28">

    {{-- HEADER --}}
    <div>
        <h1 class="text-xl font-black text-slate-900 tracking-tight">Profil</h1>
        <p class="text-sm text-slate-500 mt-0.5">Informasi akun & pengaturan password</p>
    </div>

    {{-- PROFILE CARD --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm mt-5">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-blue-700 text-white flex items-center justify-center text-xl font-black shadow-inner">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">{{ auth()->user()->name }}</h2>
                <p class="text-xs font-bold text-slate-400 mt-0.5">{{ auth()->user()->username }}</p>
                <span class="inline-flex items-center mt-2 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-wider border border-blue-100">
                    SDI Surveyor
                </span>
            </div>
        </div>
    </div>

    {{-- DETAIL --}}
    <div class="bg-white border border-slate-200 rounded-3xl mt-4 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">NIK</p>
            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->nik ?? '-' }}</p>
        </div>
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Username</p>
            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->username }}</p>
        </div>
        <div class="px-5 py-4 flex justify-between items-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Role Akses</p>
            <p class="text-sm font-black text-slate-800">SDI Surveyor</p>
        </div>
    </div>

    {{-- GANTI PASSWORD --}}
    <div x-data="{ showPasswordForm: {{ ((isset($errors) && $errors->updatePassword->any()) || session('status') === 'password-updated') ? 'true' : 'false' }} }"
         class="bg-white border border-slate-200 rounded-3xl mt-4 overflow-hidden shadow-sm">

        <button type="button"
                @click="showPasswordForm = !showPasswordForm"
                class="w-full px-5 py-4 flex items-center justify-between text-left">
            <span class="text-sm font-black text-slate-800">Ganti Password</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 transition-transform" :class="showPasswordForm ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div x-show="showPasswordForm" x-collapse class="px-5 pb-5 border-t border-slate-100 pt-4">
            <x-change-password-form />
        </div>
    </div>

    {{-- LOGOUT --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full h-12 rounded-2xl bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-black transition-all">
            LOGOUT
        </button>
    </form>

</div>
@endsection

@section('bottom-nav')
    @include('surveyor.partials.bottom-nav', ['active' => 'profil'])
@endsection
