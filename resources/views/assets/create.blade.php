@extends('layouts.app')
@section('title', 'Tambah Aset')
@section('page-title', 'Tambah Aset')
@section('content')<div class="page-intro"><div><h2>Tambah aset baru</h2><p class="muted">Lengkapi data inventaris fasilitas kampus.</p></div></div><div class="panel form-panel"><form method="POST" action="{{ route('assets.store') }}">@csrf @include('assets._form')</form></div>@endsection
