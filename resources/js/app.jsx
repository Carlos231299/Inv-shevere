import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import Purchase from './components/Purchase';
import Sale from './components/Sale';

// Dynamic Component Loader
const components = {
    'purchase-root': Purchase,
    'sale-root': Sale,
};

console.log('React App initializing...', Object.keys(components));

const mountComponents = () => {
    Object.keys(components).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            console.log(`Mounting ${id}...`);
            const root = ReactDOM.createRoot(element);
            const Component = components[id];
            root.render(
                <React.StrictMode>
                    <Component />
                </React.StrictMode>
            );
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountComponents);
} else {
    mountComponents();
}
