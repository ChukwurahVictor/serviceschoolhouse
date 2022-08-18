@extends('mail.layout')

@section('content')
  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Hi {{ $details['name'] }},</p>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">You are doing great, keep the momentum. 
  </p>
  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
    You have completed: {Number done} of modules out of the {Named Course}  
  </p>

  <center>
    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
    
      <a href="{{ $details['login'] }}" target="_blank" style="display: inline-block; color: #ffffff; background-color: #3498db; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; padding: 12px 25px; text-transform: capitalize; border-color: #3498db; margin:auto">Click here to continue learning</a> 
    </p>
  </center>

  <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">
    Keep learning, keep growing. <br>
    Explore, Learn and Grow. <br>
    Service school house
  </p>

  
@endsection

                      