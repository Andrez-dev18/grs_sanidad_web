/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./*.html",       // HTML raíz
    "./*php",
    "./**/*.html",    // HTML en subcarpetas
    "./js/**/*.js",   // Solo tu carpeta js
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}


