@extends('layouts.admin')

@section('title', 'Add New Story — Foodness')
@section('heading', 'Create New Story')
@section('subheading', 'Add a new image or video story for the storefront hero section.')

@section('actions')
    <a href="{{ route('admin.stories.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-ink/15 bg-white px-3 py-1.5 text-xs font-semibold text-ink shadow-xs hover:bg-mist/30">
        &larr; Stories List
    </a>
@endsection

@section('content')
    <div class="max-w-3xl">
        <form
            method="POST"
            action="{{ route('admin.stories.store') }}"
            enctype="multipart/form-data"
            class="admin-card admin-card-body space-y-6"
        >
            @csrf

            @include('admin.stories._form')

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-ink/10">
                <a href="{{ route('admin.stories.index') }}" class="rounded-lg px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800">
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    Publish Story &rarr;
                </button>
            </div>
        </form>
    </div>
@endsection
