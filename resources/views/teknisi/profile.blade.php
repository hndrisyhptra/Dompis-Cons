@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-6 pt-8 pb-10 rounded-b-[2rem] shadow-md relative">
        <h1 class="text-2xl font-black tracking-tight">
            Profile
        </h1>
        <p class="text-sm text-blue-100 mt-1 font-medium">
            Informasi akun Teknisi
        </p>
    </div>

    {{-- PROFILE CARD (Posisi menimpa header sedikit agar elegan) --}}
    <div class="px-5 -mt-6 relative z-10">
        <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm">
            <div class="flex items-center gap-4">
                
                {{-- AVATAR --}}
                <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-black shadow-inner">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>

                {{-- INFO SINGKAT --}}
                <div>
                    <h2 class="text-lg font-black text-slate-800 tracking-tight leading-tight">
                        {{ auth()->user()->name }}
                    </h2>
                    <p class="text-xs font-bold text-slate-400 mt-0.5">
                        {{ auth()->user()->username }}
                    </p>
                    <span class="inline-flex items-center mt-2 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-wider border border-blue-100">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>
        </div>

        {{-- DETAIL INFORMASI --}}
        <div class="bg-white border border-slate-200 rounded-3xl mt-4 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center hover:bg-slate-50 transition">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Nama Lengkap</p>
                <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
            </div>
            
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center hover:bg-slate-50 transition">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Username</p>
                <p class="text-sm font-bold text-slate-800">{{ auth()->user()->username }}</p>
            </div>
            
            <div class="px-5 py-4 flex justify-between items-center hover:bg-slate-50 transition">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Role Akses</p>
                <p class="text-sm font-black text-slate-800 uppercase">{{ auth()->user()->role }}</p>
            </div>
        </div>

        {{-- TOMBOL LOGOUT --}}
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full h-12 rounded-2xl bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-black transition-all flex items-center justify-center gap-2 active:scale-[0.98] shadow-sm">
                LOGOUT
            </button>
        </form>

    </div>

    {{-- BOTTOM NAV --}}
    @include('teknisi.partials.bottom-nav', ['active' => 'profil'])

</div>
@endsection