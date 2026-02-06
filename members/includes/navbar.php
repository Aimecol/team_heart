<nav class="bg-white shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-4">
                <img src="https://images.aimecol.com/uploads/large/team-heart_698542149457a_large.jpg" alt="Team Heart Logo" class="h-10">
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="../dashboard.php" class="text-gray-700 hover:text-blue-600 font-semibold transition">
                    Dashboard
                </a>
                <a href="../members/index.php" class="text-gray-700 hover:text-blue-600 font-semibold transition">
                    Members
                </a>
                <a href="../missions/index.php" class="text-gray-700 hover:text-blue-600 font-semibold transition">
                    Missions
                </a>
                
                <div class="relative group">
                    <button class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 font-semibold">
                        <span><?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="absolute right-0 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block z-50">
                        <a href="../profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                        <a href="../logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden md:hidden pb-4">
            <a href="../dashboard.php" class="block py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
            <a href="../members/index.php" class="block py-2 text-gray-700 hover:text-blue-600">Members</a>
            <a href="../missions/index.php" class="block py-2 text-gray-700 hover:text-blue-600">Missions</a>
            <a href="../profile.php" class="block py-2 text-gray-700 hover:text-blue-600">Profile</a>
            <a href="../logout.php" class="block py-2 text-gray-700 hover:text-blue-600">Logout</a>
        </div>
    </div>
</nav>

<script>
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>