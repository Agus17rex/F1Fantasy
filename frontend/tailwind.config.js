/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        f1: {
          red:    '#E8002D',
          dark:   '#15151E',
          gray:   '#38383F',
          light:  '#F5F5F5',
        },
      },
      fontFamily: {
        formula: ['"Formula1"', 'sans-serif'],
        sans:    ['"Inter"', 'sans-serif'],
      },
      backgroundImage: {
        'carbon': "url('/images/carbon.png')",
      },
    },
  },
  plugins: [],
}
