<style>
/* Hide vertical scrollbar for Webkit-based browsers (e.g., Chrome, Safari) */
*::-webkit-scrollbar {
    display: none;
}
</style>

<aside id="sidebar"
    class="flex hidden fixed top-0 left-0 z-20 flex-col flex-shrink-0 pt-16 w-64 h-full duration-200 lg:flex transition-width"
    aria-label="Sidebar">
    <div class="flex relative flex-col flex-1 pt-0 min-h-0 bg-[#0f0f17] border-r border-[#0f0f17]">
        <div class="flex overflow-y-auto flex-col flex-1 pt-5 pb-4">
            <div class="flex-1 px-3 space-y-1 bg-[#0f0f17]">
                <div class="mb-4 border-b border-[#0f0f17]">
                    <?php require '../dashboard/layout/profile.php';?>
                </div>
            </div>
        </div>
    </div>
</aside>