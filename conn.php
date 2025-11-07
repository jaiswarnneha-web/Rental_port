<?php
$con=mysqli_connect("localhost","root","","rent");
if(!$con)
{
    echo"<script>alert('not connected')</script>";
}
else
{
    echo"<script>alert('connected')</script>";
}
?>