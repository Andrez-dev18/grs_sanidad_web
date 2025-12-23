<?php

session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Pollo - Múltiples Dashboards">
    <title>Sistema de Sanidad- Dashboard</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard-sstyles.css">

    <style>
        /* Sidebar */
        body {
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 300px;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            transition: transform 0.3s ease, width 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 0;
            overflow: hidden;
        }

        .toggle-sidebar-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .toggle-sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .menu-item {
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 48px;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: none;
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #60a5fa;
        }

        .menu-item>span {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .menu-item>span>span {
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
            text-align: left;
        }

        .menu-item>span>i {
            flex-shrink: 0;
            width: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-item>i {
            flex-shrink: 0;
            margin-left: auto;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-link.active,
        .menu-item.active,
        .submenu-toggle.active {
            color: white !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }

        .menu-item.active,
        .submenu-toggle.active {
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 4 6px 18px rgba(0, 0, 0, 0.25);
        }

        .submenu.hidden {
            display: none !important;
        }

        .submenu:not(.hidden) {
            display: block;
        }

        .menu-link {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.4;
        }

        .submenu-toggle {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
        }

        /* Content area - ¡CORRECCIÓN CLAVE! */
        .content-wrapper {
            margin-left: 300px;
            transition: margin-left 0.3s ease;
        }

        @media (min-width: 1024px) {
            .sidebar {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: none;
            }

            .menu-toggle-mobile {
                display: none;
            }
        }

        @media (max-width: 1023px) {
            .content-wrapper {
                margin-left: 0;
            }

            .content-wrapper.sidebar-collapsed {
                margin-left: 0;
            }
        }

        /* Animaciones */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease;
        }

        /* Dashboard iframe */
        #dashboardFrame {
            width: 100%;
            min-height: calc(100vh - 80px);
            border: none;
            background: #f9fafb;
        }

        /* Loading */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Clave: Asegurar que el content-wrapper tenga margen izquierdo cuando el sidebar está abierto */
        .content-wrapper.sidebar-collapsed {
            margin-left: 0;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <!-- Logo/Header -->
        <div class="p-6 border-b border-blue-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <img src="data:image/webp;base64,UklGRix+AABXRUJQVlA4WAoAAAAwAAAAxwcAzwcASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZBTFBIjggAAAEkxm0bOaL6L3sv5/tFxAR0t2a3WyiLLJ9Yi7YWSayQaEdj14n9Np/acqJfVUXZ7LYluY0kSdL/vztbV0a4mQ6d5dl9KjQqfSwiJgCOJEmKpP8/dqhpqLtG2gORPSLy4T//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+R//x//xf/zPf/7zn//85z//+c9//vOf//znf6Duz+A24DbgLuA24DbgLuA24DbgLuA24Da4DbgNuAu4DbgNuAu4DbgNuAu4DbgNbgNuA+4CbgNuA+4CbgNuA54DTv0MwG4A2A3AbgDYDQC7AdgNALsBYD8DsBsAdgPAfgZgNwDsBmA3AOwGgN0A7AaA3QCw8TmA3QCwG4DtZwB2A8BuAHYDwG4A2M8A7AaA3QCwGQD9w3zf5++XruiCruiKLuiKvkdX9D26ogu6oiu6oCv6Hl3RBV3RFV3QFX2PruhLfNfnn+ibPua4+D/+j//jf/7zn//85z//+c9//vOf//znP/+/uzzFmfgvHov/43/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z//g3J7h7EnO8d9I7TxO0hynAS4c/41WNcSpquK/+G/O2VUa4FQ7/ov/Rp0d/8V/8d/gseK/uWrHfwFZhXE1xFlVJcd/M5VKM5ylLI5PHuAefJrEkceBTfwf//Of//znP//5z3/+85///Oc///nPf/7H//E///nPf/7zn//85z//+c//+J///I//+c9//sf//Oc//7/uTuM8yDn+i//iv/gv/ov/4r/4L/6L//7nzCOcxzjGONsm/purHP+lY4r/4r/ESmGc8riSHP9N1Laqwria4lQ7/husdmVxe+Vxa4zb8V86VjuQ0wjnqhriNMhphMOSPMcxwllO42xm+ESO+D/+j//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+83+Wt7M4f47/4r+/cR7jCOMCW9IA92CFcU7jYpSrmuFU8V9AtqtGuNq7SgOcRrmdxq0pbk1xe4qrQO441gy3jmPtLO4xxz2OLQ9w+xjj1uNYE5xqH1PcmuPWsWqC22NcrWMNcjuOqyhur7WDuMfTnCa4tdbeI9xOGvw+Ub438XuE9nGH4veIFf+NVXV/4veJPcXtVVPc3mNc3Z7oHQKPcaoq3Z3oLcJWVd2g+F1Ctyd6i7B0c+L/64onzHAlD3G6P6l3CCzJA9yDTyc4ZjlGuPvT+G+8cvw3VxH/xX9zjhz/xX9DTlWZCU6B3C45/ov/4r+pY+89xa1Brka4iv/iv0lnHfFf/Dfn7LU1xa2a4nYWp0hOM1ztPK6muBrirEFOURyTnAY5E/9lYx7kHP/FYx7kiP/mKkdxjHLEf3OVxzjiv7nKYxzxX/yXVxH/xX/xP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+8//b0d/+Ivn0FYmX8Rk/vXnzZ339yfnr4i98LSLG6P2+r/M4jv3j4+PxOI61995VVWdPbd+9SarP+2k91ecfnojR+32ee621pJT+/P377+NxHGvv0rmfS7Zv3ny2uz9L97mXmtdlWbbt1++Ttaskf+mdG/4s1ene1ZQkP+eVvcarce2tpHWelnXdfv35+/E4dpXkr+Xm3bZ1XlWlpk95kU94PWPc576XbZnWtKX89+Nx7C35q7mDs/2p6+e8xO6+GBHjvs69pjWVXNp+rF1ym/7921P3+TmOiNH73UfvY9g28T//+c9//vOf//znP//5z3/+85///I//+R//85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///nPf/7zn//85z//+c9//vOf//znP//5z3/+85///Oc///lvKVZQOCCocwAAkL4FnQEqyAfQBz5RKJJGo7AiISH32CoACglpbvvdF+kt+m/kl7lpwIwrZUr7x9M96TGfvT+7/hvUXtz/C/tX+R///N0ITspetT+Sby7zp+aDp8fQ1+kXknc1fnFkD+SfbPuG5bMUr538/PU33m/j3+v///Qd/tn/85bHr/OG/If+njrVkPaT/v7SfL+HQ//cBrl801gzSmyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeHBhapLIkbTFcbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5lbAFbbAsGVWlVcY9GU2sdgWiK57jJJhLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxb6lT3tMUp9nZ0SH9FxbqoZhREu9WGYkc/hjF/dSBJTTDvmXi9kE8y8XobRwCcxzbJL5F/BhrgRFP2WdIFDZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ4a9XXlptVSbrYl5LHhP53CubxihMSejeHXcwZRq3C9om6tcdfVQgVbIJ5l4vZBPMFpCdCXYWwayhhvJOv+azRmGs/FZPpC4fJtwv8k9LoIYdMVK/PVhw11u36pdYM0psgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8Xsgnl91CFYJOalYQzJVeE/u70sWyvxDi4CsPm6gxXFSslA7NWPJYpItQFy8XsgnmXi3JzgWxeJtfedb7tbS6P6NbMu2A8r3I0VQPQSj62vtmAtp9ENLjiRrGQWDqvX6vXYGAelwzSmyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeGFtfyRZTqHxYGPldWQGc9k5KR4c+exRyJ3jB5tvTZnq2gLhvEChsgnmXiHbILkUCa8LnSZcjz9YDNxZhN/UX/5zM7Kq9dGR2DwSNh2GnNpHNYV495s+qwhevNmX84mc/6BW6Tc02QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzKnw4M1hbsVbJrLis7JeS0SIk/ZdIvYa+MWOEEwPuODDGG8KU2QTzLxeyCA+89SZDhm8uoyLHkYV5AtFzfisy8Mh1kCa39Op5NdzHWuZgWa1OjooM0psgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8W9ObmIgcksyHEYbsF32wSkYwFQX7L0r42V7TXeQpAh0wh+Xi9kE8y8XsetglY8RkmeLbpx1Tb9BRv7VHbUvF7IJ5hKFQQ2mwqczC1QZun53QfUej4BmlNkE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXh3Q4ZT2VONRRXl0GxT016Er7m/2ikmHe5UDawZS6BkXPEChsgnmXh3UiNON0jbjRlneV/smoNXZBPFI4vpCsKcbGx3ZxHyMV81uq4klNkE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9JuDs+F8C1wP42kCWZ0RQTRGPTDk2JoKqCKkiAC4Y8vmmsGaU2QOwde2r6525gklbHCmLi5eLfUyA2NeF4THfTjKTkAi0z4ASahbUQa7nT0w0zWnT8qSJQGXnXKYnmouAKzUjZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vQ0BF2W8MmovMC7zlVMhe00lImyRxpnmjA/0JTnpMALSmyCeZeL2QTYBODSi9GigC5CyGUkPMurbiDWchh6crdsY3DnjAqVPYVDPNWdX4nyawKIoDH9EsHdSNPXyTp+0+Kl0S8l1kfEdLgxKhv0Iy6vJ+p5eL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyB80LvbuCKlSWHq3TuLF8NV4p9YqLjxEUGIYCTykE8y8XsgnmXhwqX3pXyjRGeEX99r490oJ5e52mYhTYqGAjmD8XevclM45ZrOPnSTz7YrLrQ8a63k+A91FH4ArUOf9eJq23l1aKL0fjTQfd6vBRlO/AFMWoJcXsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8KlRXbylPVtCBz8GMt65kog3kPpaWWpGe9l9nrVHxsgnmXi9kE8vfSPA3DXyfQ9ooovMhqxewk9uyMYxhiaz1A+Ug5jYwhU/kXdziAqurXkeezCe1PVjZk65u+DW6qhpffZX2M090SrYIhoQKGyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2PW1CJHvJ9U7LsCPc/CsR+jWWd8AEwqvM7ORsgnmXi9kE8MKOAheAhwLhY5OJvrpy6wZCGcqIr1jHZIXL4cte0XaVh/kNQbRTbo2k6wKVravDdhElBC+2Fvc2tZ9LWJv3KhoDwbcUp5UT6Cpo8a1gTgIq7vAyOcIfZEJYat3FZl4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPDABFqERDtTfIACrgOuNaRUBWYosev0h3rM2zPeXi9kE8y8XsetglZtBYHv/ElZ3xdrKaT8vFusxGWaBlcfkL07yzzy5KWMVMnLt3ANDT8qQ/4QSskXOkO/Cz7nJQ18La3caxiCkN9uM6wyJko0elEbQyLAv0mom2RvntYMZseYtZbB+2PcegpgH29PCfzSkZ57MvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ4Rg3b17GUpAWfXrRAP9U0En6cOJXMQVyhkn9JMaawZpTZBPL5wSssQoMTqb5mP7HcVqjM/FYUg3AVlAhXWikz/lRSq92QAwbWgCBTrZVnZLcL79WzVs+Vx3fcYbUSEfJavdd+MCEgk4Ieh62reW1ar6gNPSYQ0Hc6QoK6Pctt6JUkolQmw+ZhekgoesGaU2QTzLxeyCeZeL2QTzLxeyCeZeL2PWtgIE9rdaVp6CAjTBWFLr38qMMSv+VN6mYPKKbIJ5l4vZA5QAZbXdwlqr9HxT6M7LKmoOI7lVaBMJB+7CpXE2bl/xXn6PYm1JTIM3Rp7NWFgLnqbgBWqunhhQ0kIIzKYyyXysnYW20Q9Ch3bfRa/hlcYAvwXsgnmXi9kE8y8XsgnmXi9kE8y8XsgmuAF4kNvD7kbLN+oOixJlqiDOMo8ot4W82JyiPsnR6eZeL2QTzKn8Ep7bRlp1Qyyp346Ln3nzTGgysxRZqHvBzVz/+DYd42YN8EkzNCsDtTxxSN35U1eBUlOKj4U7HwPyuvIfNpsepE2aMUEJJmU+tELPZyrI/bprBmlNkE8y8XsgnmXi9kE8y8XsgnmXi3caGEY+YXlSYTyKNRzPSKJHuJq7qKa3BB0t1TjgqWDNKbIJ5feXHe+LxCGVzwBJrZNfthAFDYWKqgRwq4EJpJB1bCEkxoV1G8LN3VZmHpmTBNnvlN3nby0b788SO0kz9t89/bwJKdzpp10+l4wRZ/+XzqESI2pFiK7QLR5dVBHl4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vY9aAcAX8HcA9ms1KmwHhYOhpfcQzEIg0tRplxeyCeZeL2EsRdaQEedjNeE+hwG0PD7lrxeiTREPRm+NGL9ANKDmoltSeGtbbAMhQznmW+ThS/r6c1oKUGS+v7uXOLOdlbk6gx4aGbMGUB3oKeOeVQ5qEd0Nln1whOfgQm/NAE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsJmcDWdsnOp5nVTPLvVSOGVeyyRxOijOsjXmA+Hl4vZBPMqeAg0qxUJO9HdyvYKHQM0hOGCTBdHv81XkGQ9yWZYuBO5MqJY9AGe61vkuEL7sFiy2S7gNZ230Dqd1hWG7seiZ4lXRnrpVa8Qj9jpb9O7rJqT/BG+wK0eW0Fi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8NSAvJOd3UnXdGKy+64uhWHtskOIvUj/mmsGaU2QTXS2Srzm7RaPXYoGlrc49gyG1dkDsUgoqsq08yEIDq21Vxv7uQKGx61SS8HXaim6kv7ctFgKXRNwGSPWDMxLVyyoyifmmyZFdXT2EtZoLrHSJrkAKeXzTWDNKbIJ5l4vZBPMvF7IJ5l4vZBPL6Q5l92e4luj39jhCseuoVQ14E7dvR3P1a7AnmXi9kE8MAEWwFcL6dI/C+TST+/LxehI0LW86MykBWwWcyPEdeL2QVswzOLniAcHsahAU1EGPgmmQrWqVEybHvxIjaOOnCcueIFDZBPMvF7IJ5l4vZBPMvF7IJ5l4vQJv8qwhtV1CEUvKHyjldrMK7hUDalzxe9XsgnmXi9DdMdXhDOdNnpd4kDBGb50bIJq4juVVoEwkEuQlNqNN/QvLxeyCeZeL2QTw0CpudM8YU+/MctLnHi7niohqwZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4t9HAIguQmwBUmS4Ljwa7svPK8/H0dDh5eL2QTYGUvTzG9eqqr8iCU2QOVH/vtfShDEgYJqAz9hLSmyCeZeL2QTzLrKJY+IeziAay1Pq5wzcVIl4+Rg+KzLxeyCeZeL2QTzLxeyCeZeL2QTzLrMgwLHQGeAEVWR8oflIpgvPZl4vZBNgE4rfsqu8CqWEvh80gniBMQbkT0cQ3+aRFi5NoXJvKCprBmlNkE8y8XsetfCrtn8UT5XW0nr44AZrjaIznkEFZrmW/RHiBQ2QTzLxeyCeZeL2QTzLxeyCeZeL0CupPXzAfPZhfwxSU2EoVKR7Bej4eXi9j5GZ623HjBWf3rKPZ2lS2ULAgUNDYcQWqqFh5fVXmDJHvx7JlBeyCeZeL2QTzLxegXb5c7donG6NkqMX8WSCoDmXsgnmXi9kE8y8XsgnmXi9kE8y8XsgdgmfH3C7uy1psueKSRwLegBMrMKhbh8I6jS0vZBPMutg95lKs1hkLePXAKjxo2wqY5F7CkCDAud//Svq9ADOfmlNkE8y8XsgnmXi9j0oIwF7p8gMduUpFhx6nMd1Zl4vZBPMvF7IJ5l4vZBPMvF7IJ5l3iFr96bHr+5LbhzpN1c5fNNRAOTpfwlK2988RN6MTc2b8UwOXDoZwsGpL6QLelYU/pPAKk8QKGyCeZeL2QTzLw5V+AL3gSmQict04W1sArUIFDZBPMvF7IJ5l4vZBPMvF7IJ5l4vRDy2JmmoYH1EC9Hw8vF6JNMjSuQfBwH/hoyZ7bzfBex7F+y6Rewy1rnv4G7snhndBheL2QTzLxeyCeZeL0FBGAvuuNNEmESicvmmsGaU2QTzLxeyCeZeL2QTzLxewpJeUHWhXCkI2MzntbIJ4alWYS4aI97ze18ErItFgUNhYuQHfOimxz3UKelc60e09WZeL2QTzLxeyCeZeHKvwBfdhTh/7nWvZBPMvF7IJ5l4vZBPMvF7IJ5l4vZA613JWD4pe7uxj25eLfUdBqC1VItwggIhue95l1sztVtXJGdSsbCyDFPZD1LitWy8XsgnmXi9kE8y8XsI3PGD4WAbGD4rMvF7IJ5l4vZBPMvF7IJ5l4vZBPL7Ek/F25zgG4pYETQsB+bqRUgUNhCkbRjEUZXq4Mmgdqc7sasGZjsfykZLyCZmZJN3sR/2k3JPMvF7IJ5l4vZBPMvF7CNzxg+FgGPAE8y8XsgnmXi9kE8y8XsgnmXi9kE8yp5+omn4u3OdVebl0PV3mcdfoMzHErry8Cs/gDS5ooYPPP1xisy6vLh0KUV4RO4wNss+mYZt1NYM0psgnmXi9kE8y8XokJ0caqwF92FMNTYvZBPMvF7IJ5l4vZBPMvF7IJ5l4vYs5FFrYFqC2tY0v/JXZ6svnJWq3HJz0QHQolgOFO3C5zUHF9HXLFQrYY8Y9zxLNcFi9kE8y8XsgnmXi9kE8vnjbAusAn9Mfe69Kj6V6FzGYPisy8XsgnmXi9kE8y8XsgnmXi9kE8xkE1Gwrm7AANkDlCyKrfEDAppGcXF6vhilT58E10TzLw6DcHBBaqmCYf5l+9LeJ801gzSmyCeZeL2QTzLxb4ux7IqJMGQ1eBQF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IO9Nkwr2BPL3zVN94jC6txjj466b679nKKWFkE1cR3dnVn9hatx/gB9+qjquXi9kE8y8XsgnmXi9kE8ypCsIsoEUaKgJDwqC0hrQ/0qpR7quJmvMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvD2geMRdOY4yIfM9HyRxAahRM4JOtjGTK8cknwzSmg9UP8gZmljw0kwUT8gGHisy8XsgnmXi9kE8y8XsgmwE7g9ouK4wJb8p9AbyHeqtfdJBqTkDiqxJUpsgnmXi9kE8y8XsgnmXi9kE8y8Xsf5Y1ZdTwiHzPbV0Em1zTbNVBZY2Dlexi431tieZUjOuUFA4Z5JeU0GTD1jkYWa4lzxAobIJ5l4vZBPMvF7IHUctSJuYxnapDMEorD1SYqUiJjGyrnL5prBmlNkE8y8XsgnmXi9kE8y8XsUzTrukcqeXiKDUltXMG1OzMd2Ta+M4VMq1uZr+sahM7++3524dB102eM5yvsE+0nHZCASn1NYM0psgnmXi9kE8y8XseiAQnKWOGHgUKxUjGilHJsKUhlbukx04Box6wZpTZBPMvF7IJ5l4vZBPMvF7IJ5l1sR4/fLvqHb0KKBRU1eK3a4c8N5uj1MOufS3TfUpMtXP6uCl9ddvPWO4ywSf/7rPRKWvGfhMJJffcjxeyCeZeL2QTzLxeyCeZeL0SK2PNi4AzDoVLPSBMTsek66gF+C9kE8y8XsgnmXi9kE8y8XsgnmXi9j1IBk9b4NxPz/pTWCUDn9DWpJjfXPc/WebJ7quUDwgYzzrzgue2A3X3wT4CsfIko1WGgQGxHTkPD9TWDNKbIJ5l4vZBPMvF7IHTS77BesI4+DMCxvpeWusn8GEOQ7XFoBlTJ//sXsgnmXi9kE8y8XsgnmXi9kE8y8XsgnhTct5+4XYmEFLO02QR1bzLOxAgEeU10Yr+rSj0KEiJOMa35eL2QTzLxeyCeZeL2QTzLxb0tZU3+/zOlv8ZAgTGfM9FGR2i5GKIBXY9cn1NYM0psgnmXi9kE8y8XsgnmXi9kE8yp+TA8wSFjKVCzfildB7qRf4CoCGIFDZBPMvF7IJ5l4vZBPMvF7IJ5l1d6S0wlaMV1xVYPMurrbPhfGd1RmtxeyCeZeL2QTzLxeyCeZeL2QTzLxeyB1ruSsHxWXwH415pt/R+7iMqPMHxWZeL2QTzLxeyCeZeL2QTzLxex63GMBdzQHsbkTT2BdCzgn088KEgO/i/c1lfB/J5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4t6CqsSUCitfkcFHf+/yFVmXeV1I4Z13mYN1EOY+yr2ZeL2QTzLxeyCeZeL2QTzLxeyCbAJ7JIaGWtnkGwmiaUNhMyWgJ/xGAe2JYM0psgnmXi9kE8y8XsgnmXi9kE8y8XseiX4YlBtv/wIt8U1gyFcN1pAXCmXz0pMLK3NwXsgnmXi9kE8y8XsgnmXi9kE8vvRzVUWTV5Er9eJJNGCEAqagVF2wjKJCKG912mqslDZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMqPv0VNhPxn7vrsCeZd3kajg7aauUW1yQ01nhnGm4wfFZl4vZBPMvF7IJ5l4vZBPMvDuk8lGaKExf6HJvkrYo94J5e8O+RJIf0yewIqHljqmzTWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF6HJHNhlq7nfFzmFglJ3WDMw8yMh5fik/sqAidhUTFueIFDZBPMvF7IJ5l4vZBPMvDur8TH2xvmk2XNMHdCeIB+roLMT5ZwWJlDm/FZl4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vQLYJJUwKcLl+MZF7TTWDeoa8mWSmByW8LLhAB6zWQTzLxeyCeZeL2QTzLxeyCeGQLAr/RoWf/fQM5KOcvlG8h70FScx7lFLFwnYlNkE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8W9jibVkoxAQfkcuMcyAJcXsfsPCCoIR5P/H5+k7uiBpwnJ5l4vZBPMvF7IJ5l4vZBPL5tgSLt+RM9z4bQmzaJp8YlDY9TYOkc3QIOLrEuXIRnzTWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IuGMB98f9uVvMalzTWDJjIsCiyAaHhFUu7LT32L5l4vZBPMvF7IJ5l4vZBNgp3CPQ8LXCZSIMAbuxd8B8f2ChsJj1cmCpjSbbuHm/2ic6wZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPL3ln++BSxBFqsI9YaG+KmsGREvX8ktBDV71yOlgJ2CAX4L2QTzLxeyCeZeL2QTwyB2xnOp36cDidnzyKGVIRSxaP6mryF/sVa+a+dfi1BLuF4uXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8ONglL+z6KY8luX6SoYFvxWZYfwtZ+duPPmOeDF+V/7usgEvE3jC8XsgnmXi9kE8y8XsLIjJ2gFiM2g7570eyv2powzSMYM0o+r9VjQ5CGvEDaGSD7+Stc5fNNYM0psgnmXi9kE8y8XsgnmXi9kE8y8XsPbQJJ/uAz9ZB2Nj8niBQ2FR86GAL0fbWHIqwZ0XW3nVkkIXi9kE8y8XsgnmXi9JuHSWHl/DcqHRGig0NeHzTV4tnY+ymZBs/tCrl8JzGaU2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxewiJPEGRF4oXTFHAitKqd/FgzSmx6kVxqXDEHY+WPI7csI4sNrOUCDy9kE8y8XsgnmXi31Maejf33lRJmeaSR/VJ9/qPpjeC9kE1hhIrY9UOcmbk5zNGZw7qU1gzSmyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeLmoCde1mVbaw+Pve2qiWksGaU2PT0+u5UwfWhsGRLT/XEzrIGHSJdVAlETzLxeyCeZeLjmhfkBuIY3Zal9bDewZAdckAUN4gK67SwZpR28iknXTakaWdq2dfTsiHMG26fU1gzSmyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeHPgLA1wY75Z9R1A244UZTZBPMqfvPN2qzLWfn+XiZKrRnWCo/ErkI6ghznOXzTWDNKaJYJ2b+rAbC2WxghNmwb/t1mwSNO2dia36WDNKaEjTSb50M0t3zpCd5epDwYGhzfisy8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi3o4A1SUjwH0rlQNacnQv+IFDZBPCmmAtoVbJ49B7+xnv+qNuMIvM0GTS8g7uK+j1DZXmXi9j2bpq1U8PD1RhnrXIEMLuMyuh9efPbIqe4k0psgdimhV8vk120Ci7hQfBL8aIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IHKU2SxC0WYLXUDU9sXcvmmsGZfjglExwi0HaWmot/FpQN/IO6aQmHVvjHnqfhnXAEeTqBR/edcg/wnNElGtXcHBP9EjKChP7tcayOIkVgFRkz3y1rOaRqNP7JmzSL2QTzKngZca5DOJINUZXTx0nSJhGoeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeXvyinPjkHo5GuFFlnTTblEChsgnmXkRfUpRSvE0LsKH52Zznb90Ey6UeiuFHHHgZpHEHu6dc879ga1Laq8FNIaIgifRI4ClOqHV3jLBmlNj04gIuxezLeYL3PDE0ywUx7+SheL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2EzDzSFe+FFwvZydHUfQh1rxeyCeZeL0Nayc6Oj1T+uOqeYiPO0KL8eFKbIJ4VJd4WiorYtZmbh3HRsAikyY01gzSmyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLw6K0drts0BBLY3Ozt7zC7oeT3eDjIFDZBPMvF7IJ5l4vZBPMvDnvdS6eANmXovD2hruIppJ9luDj8vF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5+23zLGph0xqUcZwtsdj8fRXbmF7IJ5l4vZBPMvF7IJ5l4dC+UxambCdzWsEfhR85brg2yfwYSi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XW4U8cTudkr56umm/C79DschfR/xK6msGaU2QTzLxeyCeZeHQP/E7huJVtTj/4KAATygPS3sVmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE80cf3OR+VCQXCEASq71bEm7nGPtLJ4ARHBeyCeZeL2QTzLxewlO+aQt/szE/+4vfr804ZiwVWvSQt5bb8VmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE80JXjEBWG3PcCggyY8Ez7zyWJIkRf+tEmDeBQ2QTzLxeyCeXzkpA0Qnh5psRuZC2Mb3vlSxhVosdTshig8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8vpV6EomOwfaX5CJmqCTZDE7/NG3wIXb+hodwwiwXQonmXi9kE8ypA+dIk5Lu2S0K0vrykCXVQLO+glE7BCLGQiTXy8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8Xsi68MxRILCd3Egy4LoSa99ckibXJkjDdszbnPtLDFgb+f+FGdrTWDMw4JXDuJabK7lUk1o3EufuNoSW0ML/XveyxlBp/Nq00hZoTm/FZl4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4x4hesNPFXdrfL5pqppfqUa4t0baR+ublYvVt+alkrlkqKJnY52iAMOhvcvV14TRVE/fKhLdvPKM9wcLHRjb/0XqB/rEY17sqv1Pv8VrBmlNkE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y838b44CRPX8M64q/5hY85/6/YMo90NShaPe4PjP5Fnj+H9jh7+J7a0Wz0bNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMust4M3CBClh2y+VPE5UPxdueIFDZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5esCan6q7kgOyBgLlHw2PxdueIFDZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5fBtEyadCgr5fiCJnOGVjM5p9TWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZA6h9/WC32+vd2MQZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4d+/XJXSeGdjM5p9TWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZA6h9/WC32+vd2MQZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4d+/XJXSeGdjM5p9TWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZA6h9/WC32+vd2MQZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4d+/XJXSeGdjM5p9TWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZA6h9/WC32+vd2MQZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4d+/XJXSeGdjM5p9TWDNKbIJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZA6h9/WC32+vd2MQZpTZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l1swbFGt7W9AK8hKUJj6cCvjbXcTS2zMQFLq9zki6ByEQ00Q2TdvQ2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCbBTi++BFKjHI4V7TNLWnl9JDFpYsNlEEBzWAwr4IKOY94eDn/bX81iXqP7zBJ3zWh1EbNd858vF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5lVuUMirOd3C8DFj9C4VPFBdAAEDPncvmfB5dF9zSAGzXa/EmWsjgiJXt0CyqlGAMxOK/KWTljp5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vYWJZHFyqZ0cnJzvY+mRuuFZ095RsydOjW5ae8Xsgnh3HDrixSHGHEv7UaxYxdLBmlNkE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXjAO457sFU1z3XTm2VCwwf5VBk4otREqT0OVxTUjOvDJ8plUbDFg9+jfINzKrJ/BqTSWwkEtXun6AEQgs2fRg855YW5g1OgO8I/6W8o9PMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMutmcYgz2HzRXpEYWMZF+tFMHz9KJeeMT+I4kkNmVNk0i+fhoTyyIYd++p62Dkch3Cs/wBl8vC8KXpNOX33hFpKSGtBGzHucqQEQ/hdzbQ+8sRzJ2wSrCmkb2DrYV66GLRXrXSmirzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxfRtXMW+qjH+EDW6ZDn9Few55GAeocSPL8BjUaxWVu8rPgkh1Va2CatUZoXluL+buvdvfTyJ4k4bnoKnQNbBWQFyLV3XdM/1tL17MKj7v+ePi2uACqDFW0DI4eXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9hcH8aoHrx1kFI1tBH8z1kTc1UMDKVKLgbeEuMce8KW89nvp010Htjv2MTbUcfa5/ZqYVf2gw/OMxpjRN+WVhR5GektuKew5rlzhdrrBmlNkE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9B7udQP9uTLbTsztsFyYx+TjUHQJpJoRRWZhtUS+tsMu9f6O0SgQR+LPp4nH8v4AmG66NoaXEwy2UGaU2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QUf7mi9kE8y8XsJeOxmHsg7aA0G/WtGyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeShWZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2QTzLxeyCeZeL2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"
                            alt="Sanidad Logo">
                    </div>
                    <div>
                        <h2 class="text-white font-bold text-lg">SANIDAD</h2>
                        <p class="text-blue-300 text-xs">Gestión </p>
                    </div>
                </div>
                <button onclick="toggleSidebarCollapse()" class="lg:hidden text-white hover:text-blue-200">
                    <i class="fas fa-times text-x"></i>
                </button>
            </div>
        </div>

        <!-- Menu -->
        <!-- Navbar actualizado -->
        <nav class="p-4 space-y-2 overflow-y-auto" style="max-height: calc(100vh - 130px);">
            <div class="mb-4">
                <p class="text-blue-300 text-xs uppercase font-semibold mb-2 px-3">Dashboards</p>
            </div>
            <div class="menu-group">
                <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                    onclick="activateAndLoad(this, 'dashboard-dashboard.php', '📊 Dashboard',  'Resumen visual de los datos registrados en el sistema')">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-chart-line"></i>
                        <span class="font-medium">1.- Dashboard</span>
                    </span>
                </button>

            </div>

            <div class="menu-group">
                <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                    onclick="activateAndLoad(this, 'dashboard-registro-muestras.php', '📋 Registro de Muestras', 'Registro del pedido de muestra')">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-vial"></i>
                        <span class="font-medium">2.- Registro de Muestras</span>
                    </span>
                </button>

            </div>

            <div class="menu-group">
                <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                    onclick="activateAndLoad(this, 'dashboard-rpta-laboratorio.php', '📋 Registro Laboratorio', 'Registro de la respuesta  del laboratorio')">
                    <span class="flex items-center gap-3">
                        <i class="fa-solid fa-atom"></i>
                        <span class="font-medium">3.- Registro Laboratorio</span>
                    </span>
                </button>

            </div>
            <div class="menu-group">
                <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                    onclick="activateAndLoad(this, 'dashboard-planificacion.php', '📅 Planificación', 'Registro de la planificación')">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-calendar"></i>
                        <span class="font-medium">4.- Planificación</span>
                    </span>
                </button>

            </div>
            <div class="menu-group">
                <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                    onclick="toggleSubmenu('submenu-reporte')">
                    <span class="flex items-center gap-3">
                        <i class="fa-solid fa-file"></i>
                        <span class="font-medium">5.- Reportes</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </button>

                <div id="submenu-reporte" class="submenu hidden pl-10 mt-2 space-y-2">
                    <a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-reportes.php', '📈 Formato de Muestras', 'Formato PDF y envío de correo de los pedidos de muestra')"
                        class="menu-link block text-gray-400 hover:text-white">Formato de Muestras</a>
                    <!--a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-registro-muestras-cabecera.php', '🗃️ Listado de Muestras', 'Listado de los pedidos de muestra registrados en el sistema')"
                        class="menu-link block text-gray-400 hover:text-white">Listado de Muestras</a>
                    <a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-detalle-muestras.php', '📁 Listado de Solicitudes', 'Listado de las solicitudes registradas en el sistema')"
                        class="menu-link block text-gray-400 hover:text-white">Listado de Solicitudes</a>
                    <a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-registro-resultados.php', '🗒️ Resultados Cualitativos', 'Listado de los resultados cualitativos registrados en el sistema')"
                        class="menu-link block text-gray-400 hover:text-white">Resultados Cualitativos</a>

                    <a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-registro-resultados-pollo-bb.php', '📊 Resultados Cuantitativos Pollo Bebe', 'Listado de los resultados cuantitativos de pollo bebe registrados en el sistema')"
                        class="menu-link block text-gray-400 hover:text-white">Resultados Cuantitativos Pollo Bebe</a>
                    <a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-registro-resultados-pollo-adulto.php', '📊 Resultados Cuantitativos Pollo Adulto', 'Listado de los resultados cuantitativos de pollo adulto registrados en el sistema')"
                        class="menu-link block text-gray-400 hover:text-white">Resultados cuantitativos pollo Adulto</a> -->
                    <a href="#"
                        onclick="selectMenuItem(this); loadDashboardAndData('dashboard-seguimiento.php', '📊 Seguimiento', 'Seguimiento de los resultados cualitativo y cuantitativo registrados en el sistema')"
                        class="menu-link block text-gray-400 hover:text-white">Seguimiento</a>

                </div>



                <!-- MANTENIMIENTOS -->
                <div class="menu-group">
                    <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                        onclick="toggleSubmenu('submenu-maestros-sistema')">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-cogs w-5"></i>
                            <span class="font-medium">6.- Configuracion</span>
                        </span>
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>

                    <div id="submenu-maestros-sistema" class="submenu hidden pl-10 mt-2 space-y-2">

                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-empresas-transporte.php','🚚 Empresas de transporte', 'Administre las empresas de transporte registradas en el sistema')"
                            class="menu-link block text-gray-400 hover:text-white">Empresas de transporte</a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-laboratorio.php','🔬 Laboratorio', 'Administre los laboratorios registrados en el sistema')"
                            class="menu-link block text-gray-400 hover:text-white">Laboratorios</a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-tipo-muestra.php','🧪 Tipo muestra', 'Administre los tipos de muestra registrados en el sistema')"
                            class="menu-link block text-gray-400 hover:text-white">Tipos de Muestra</a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-analisis.php','🔍 Analisis', 'Administre los analisis registrados en el sistema')"
                            class="menu-link block text-gray-400 hover:text-white">Tipos de Analisis</a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-paquete-analisis.php','📦 Paquete analisis', 'Administre los paquetes de analisis registrados en el sistema')"
                            class="menu-link block text-gray-400 hover:text-white">Paquetes de Analisis</a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-respuesta.php','🛠️ Tipos de Respuesta', 'Administre los tipos de respuestas registrados de los analisis')"
                            class="menu-link block text-gray-400 hover:text-white">Tipos de Respuesta</a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('dashboard-correo-contactos.php','📧 Correo y Contactos', 'Administre  tu cuenta de correo y tus contactos para envío de ')"
                            class="menu-link block text-gray-400 hover:text-white">Correo contactos</a>
                    </div>
                </div>



        </nav>



    </aside>

    <!-- Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebarCollapse()"></div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <div class="px-4 py-4 flex items-center justify-between">
                <div class="flex items-start gap-4">
                    <!-- Botón PARA DESKTOP (ocultar sidebar) -->
                    <button class="mt-1" onclick="toggleSidebarCollapse()">
                        <i class="fas fa-bars text-gray-600 text-lg"></i>
                    </button>
                    <div>
                        <h1 id="dashboardTitle" class="text-2xl font-bold text-gray-800 leading-tight">

                        </h1>
                        <p id="dashboardsubTitle" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                </div>

                <!-- User Dropdown -->
                <details class="relative">
                    <summary class="flex items-center gap-3 cursor-pointer select-none list-none">
                        <div class="text-right">
                            <p id="userName" class="text-sm font-semibold text-gray-700">
                                <?php echo htmlspecialchars($_SESSION['usuario'] ?? 'usuario'); ?>
                            </p>
                            <p id="rolUser" class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'nombre'); ?>
                            </p>
                        </div>
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white"></i>
                        </div>
                    </summary>

                    <div class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user text-gray-500 mr-2"></i> Perfil
                        </a>
                        <hr class="border-gray-200 my-1" />
                        <a href="#" onclick="logout()" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
                        </a>
                    </div>
                </details>
            </div>
        </header>


        <!-- Dashboard Content -->
        <main class="">
            <div id="loadingIndicator" class="loading hidden">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <iframe id="dashboardFrame"></iframe>
        </main>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loadingIndicator').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        // Registrar correctamente el evento 'load' del iframe
        document.getElementById('dashboardFrame').addEventListener('load', hideLoading);

        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            const contentWrapper = document.querySelector('.content-wrapper');
            const overlay = document.getElementById('sidebarOverlay');

            const isCollapsed = sidebar.classList.contains('collapsed');

            if (isCollapsed) {
                // Vamos a abrir el sidebar (solo en móvil)
                sidebar.classList.remove('collapsed');
                if (window.innerWidth < 1024) {
                    overlay.classList.add('active');
                }
            } else {
                // Vamos a cerrar el sidebar
                sidebar.classList.add('collapsed');
                overlay.classList.remove('active'); // ← ¡IMPORTANTE!
            }

            contentWrapper.classList.toggle('sidebar-collapsed', !isCollapsed);
        }

        function toggleSubmenu(id, btn) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('hidden');
            if (btn) {
                btn.classList.toggle('active');
            }
        }

        function markActiveElement(element) {
            document.querySelectorAll('.menu-link.active, .menu-item.active, .submenu-toggle.active')
                .forEach(el => el.classList.remove('active'));
            if (!element) return;
            element.classList.add('active');

            const menuGroup = element.closest('.menu-group');
            if (menuGroup) {
                const topMenuItem = menuGroup.querySelector('.menu-item');
                if (topMenuItem) topMenuItem.classList.add('active');
            }
        }

        function selectMenuItem(element) {
            markActiveElement(element);
        }

        function activateAndLoad(buttonOrLink, dashboardUrl, title, subtitle) {
            markActiveElement(buttonOrLink);
            loadDashboardAndData(dashboardUrl, title, subtitle);
        }

        function loadDashboardAndData(dashboardUrl, title, subtitle) {
            const frame = document.getElementById('dashboardFrame');
            document.getElementById('dashboardTitle').textContent = title;
            document.getElementById('dashboardsubTitle').textContent = subtitle;
            showLoading();
            // Siempre reiniciar el src para forzar el evento 'load'
            frame.src = dashboardUrl;
            if (window.innerWidth < 1024) {
                toggleSidebarCollapse();
            }

        }

        // Inicialización al cargar la página
        window.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const contentWrapper = document.querySelector('.content-wrapper');

            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('collapsed');
                contentWrapper.classList.remove('sidebar-collapsed');
                overlay.classList.remove('active'); 
            } else {
                sidebar.classList.add('collapsed');
                contentWrapper.classList.add('sidebar-collapsed');
                overlay.classList.remove('active'); 
            }

            // Cargar dashboard por defecto
            const defaultMenuItem = document.querySelector('[onclick*="dashboard-dashboard.php"]');
            if (defaultMenuItem) {
                activateAndLoad(defaultMenuItem, 'dashboard-dashboard.php', '📊 Dashboard de Reportes', 'Resumen visual de los datos registrados en el sistema');
            } else {
                const frame = document.getElementById('dashboardFrame');
                frame.src = 'dashboard-dashboard.php';
                document.getElementById('dashboardTitle').textContent = '📊 Dashboard de Reportes';
                document.getElementById('dashboardsubTitle').textContent = 'Resumen visual de los datos registrados en el sistema';
            }
        });

        // Ajuste al redimensionar
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth >= 1024) {
                sidebar.classList.add('open');
                overlay.classList.remove('active');
            }
        });
    </script>
    <script src="logout.js"></script>

</body>

</html>