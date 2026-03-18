</div> </main> </div> <script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
    document.body.style.overflow = document.getElementById('sidebar').classList.contains('show') ? 'hidden' : '';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('show');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
</script>
</body>
</html>