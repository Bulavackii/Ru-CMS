@extends('layouts.guest')

@section('title', __('frontend.auth.verify_title'))
@section('eyebrow', __('frontend.auth.eyebrow_verify'))
@section('heading', __('frontend.auth.verify_title'))
@section('lead', __('frontend.auth.verify_lead'))

@section('aside_title', __('frontend.auth.aside_verify_title'))
@section('aside_text', __('frontend.auth.aside_verify_text'))

@section('content')
    @if (session('status') === 'verification-link-sent')
        <div class="au-note au-note--ok">
            <i class="fas fa-circle-check"></i>
            <span>{{ __('frontend.auth.verify_sent') }}</span>
        </div>
    @endif

    <div class="au-note au-note--info">
        <i class="fas fa-circle-info"></i>
        <span>{{ __('frontend.auth.verify_hint') }}</span>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="au-btn">
            <i class="fas fa-paper-plane"></i> {{ __('frontend.auth.verify_resend') }}
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:10px">
        @csrf
        <button type="submit" class="au-btn au-btn--ghost">
            <i class="fas fa-arrow-right-from-bracket"></i> {{ __('frontend.auth.logout') }}
        </button>
    </form>
@endsection
