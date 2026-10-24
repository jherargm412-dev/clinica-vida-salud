{{--
  Modo claro / oscuro (Bootstrap 5.3: atributo data-bs-theme en <html>).
  Se ejecuta en el <head>, ANTES de dibujar la página, para que no se vea un "parpadeo" blanco.
  Orden: lo que el usuario eligió (guardado en este navegador) → si no eligió, el modo de su computadora.
--}}
<script>
    (function () {
        var tema = null;
        try { tema = localStorage.getItem('cvs-tema'); } catch (e) {}
        if (tema !== 'dark' && tema !== 'light') {
            tema = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', tema);
    })();
</script>
