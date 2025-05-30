/** @type {import('tailwindcss').Config} */
export default {
  content: ["./src/views/*.php"],
  safelist: ['bg-primary-200', 'hover:bg-primary-400', 'text-primary-200'],
  theme: {
    extend: {
      boxShadow: {
        'custom': '0 4px 12px rgba(0, 0, 0, 0.1)',
      },
      colors: {
        'beige': '#F9FAFB',
        'school1': '#8ABAE2',
        'school2': '#4A73AF',
        primary: {
          '100': '#8FADC9',
          '200': '#8ABAE2',
          '300': '#5490BA',
          '400': '#4A73AF'
        },
        secondary: {'100':'#FBC13B', '200':'#FDAC32'}
      }
    },
  },
  plugins: [],
}

