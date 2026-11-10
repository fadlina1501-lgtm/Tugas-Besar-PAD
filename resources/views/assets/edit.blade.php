@extends('layouts.app')
@section('title', 'Edit Aset')
@section('page-title', 'Edit Aset')
@section('content')<div class="page-intro"><div><h2>Edit {{ $asset->asset_code }}</h2><p class="muted">Perbarui informasi dan kondisi aset.</p></div></div><div class="panel form-panel"><form method="POST" action="{{ route('assets.update',$asset) }}">@csrf @method('PUT') @include('assets._form')</form></div>@endsection
