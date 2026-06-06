<?php

// [VERCEL OPTIMIZATION]: Titik masuk (entrypoint) khusus untuk Vercel Serverless Function.
// File ini akan menjembatani *request* dari Vercel menuju *core* utama Laravel tanpa membebani memori.

require __DIR__.'/../public/index.php';
