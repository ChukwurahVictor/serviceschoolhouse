@extends('mail.layout')

@section('image')
  <img src="{{$details['image'] ? $details['image'] : 'https://serviceschoolhouse.com/static/media/logo_new.d1db4956.png'
 }}" title="Logo" width="150px" height="auto" style="margin-top: 1rem;" />
@endsection

@section('content')
  {!! $details['body'] !!}
@endsection

                      