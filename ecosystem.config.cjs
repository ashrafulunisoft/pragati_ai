module.exports = {
  apps: [
    {
      name: 'pragati-ai-web',
      script: 'artisan',
      args: 'serve --host=0.0.0.0 --port=8000',
      interpreter: 'php',
      cwd: __dirname,
      autorestart: true,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'production',
      },
    },
    {
      name: 'pragati-ai-queue',
      script: 'artisan',
      args: 'queue:work --tries=3 --timeout=90',
      interpreter: 'php',
      cwd: __dirname,
      autorestart: true,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'production',
      },
    },
  ],
};
