import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import StoreLayout from '@/layouts/StoreLayout';

createInertiaApp({
    title: (title) => (title ? `${title} · RoloBox` : 'RoloBox · Tu antojo, en una caja'),
    layout: (name) => (name === 'PagoPrueba' ? null : StoreLayout),
    progress: { color: '#b14b1c' },
});
