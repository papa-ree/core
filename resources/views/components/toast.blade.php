@if (session('success'))
    <script>
        window.showToast( @js(session('success')), 'success' )
    </script>
@endif
@if (session('error'))
    <script>
        window.showToast( @js(session('error')), 'success' )
    </script>
@endif