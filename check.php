<?php

$img = imagecreatefrompng('public/images/logo-sekolah.png');
echo imagesx($img).'x'.imagesy($img);
