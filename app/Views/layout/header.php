<?php
// app/Views/layout/header.php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Meu SaaS' ?></title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; color: #333; margin: 0; }
        .container { max-width: 960px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        footer { text-align: center; padding: 20px; margin-top: 20px; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>

<div class="container">
