import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import StoreLayout from '@/layouts/StoreLayout';

createInertiaApp({
    title: (title) => (title ? `${title} · Twist & Rolls` : 'Twist & Rolls · Panadería artesanal por encargo'),
    layout: (name) => (name === 'PagoPrueba' ? null : StoreLayout),
    progress: { color: '#f0a848' },
});
