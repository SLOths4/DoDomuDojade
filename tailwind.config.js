/** @type {import('tailwindcss').Config} */
export default {
  content: ["./src/views/*.php"],
  theme: {
    extend: {
      boxShadow: {
        'custom': '0 4px 12px rgba(0, 0, 0, 0.1)',
      },
      colors: {
        'beige': '#F9FAFB',
        'school1': '#8ABAE2',
        'school2': '#4A73AF'
      }
    },
  },
  plugins: [],
}

