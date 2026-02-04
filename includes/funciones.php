<?php

function formatearFecha($fechaISO) {
    $timestamp = strtotime($fechaISO);
    if ($timestamp === false) {
        return $fechaISO;
    }
    return date('d/m/Y', $timestamp);
}