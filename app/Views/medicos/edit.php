<?php

$formMode = 'edit';
$pageTitle = 'Editar Medico';
$formAction = '/medicos/update/' . (int) ($medico->id ?? 0);

require __DIR__ . '/form.php';
