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
        'school2': '#4A73AF',
        primary: {
          '100': '#8FADC9',  // Najjaśniejszy, pastelowy błękit
          '200': '#8ABAE2',  // Jasny, lekko nasycony niebieski
          '300': '#5490BA',  // Średni, chłodny niebieski
          '400': '#4A73AF'
        },
        secondary: {'100':'#FBC13B', '200':'#FDAC32'}
      }
    },
  },
  plugins: [],
}

