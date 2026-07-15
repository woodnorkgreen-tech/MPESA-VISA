/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.vue',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // Safaricom green
                safaricom: { DEFAULT: '#00A651', dark: '#007A3D', light: '#35D06F' },
                // M-PESA red
                mpesa:     { DEFAULT: '#C8102E', dark: '#9B0D22' },
                // Visa navy/gold
                visa:      { DEFAULT: '#1A1F71', gold: '#F7B600' },
            },
            fontFamily: {
                display: ['Outfit', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            // Slimmed weight scale: existing font-bold/black classes render
            // lighter app-wide via the variable font (100–900)
            fontWeight: {
                medium:    '400',
                semibold:  '450',
                bold:      '500',
                extrabold: '550',
                black:     '600',
            },
        },
    },
    plugins: [],
}
