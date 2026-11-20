/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/**/*.blade.php', './resources/**/*.js', './resources/**/*.vue'],
    theme: {
        extend: {
            colors: {
                canvas: '#0A0A0A',
                panel: '#151518',
                hairline: '#2A2A2E',
                acid: '#B4FF39',
            },
        },
    },
    plugins: [],
};
