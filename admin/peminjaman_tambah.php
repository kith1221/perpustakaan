<?php
// Set page title
$pageTitle = 'Tambah Peminjaman Buku';

// Include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';

// Get list of students
$query = "SELECT * FROM siswa ORDER BY kelas ASC, nama ASC";
$result = mysqli_query($conn, $query);
$students = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

// Get list of books with categories
$query = "SELECT b.*, k.nama_kategori 
          FROM buku b 
          LEFT JOIN kategori_buku k ON b.kategori_id = k.id 
          ORDER BY k.nama_kategori ASC, b.judul ASC";
$result = mysqli_query($conn, $query);
$books = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
}

// Get all categories for filter
$query = "SELECT * FROM kategori_buku ORDER BY nama_kategori";
$result = mysqli_query($conn, $query);
$categories = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_loan'])) {
    // Get form data
    $studentId = (int)$_POST['siswa_id'];
    $bookIds = isset($_POST['buku_id']) ? array_map('intval', $_POST['buku_id']) : [];
    $borrowDate = cleanInput($_POST['tanggal_pinjam']);
    $duration = (int)$_POST['lama_pinjam'];
    
    // Validate form data
    if (empty($studentId) || empty($bookIds) || empty($borrowDate) || empty($duration)) {
        $error = 'Harap isi semua field';
    } else {
        // Remove any duplicate book IDs
        $bookIds = array_unique($bookIds);
        
        // Check if there are more than 3 books selected
        if (count($bookIds) > 3) {
            $error = 'Siswa maksimal boleh meminjam 3 buku secara bersamaan';
        } else {
            // Check if student exists
            $query = "SELECT * FROM siswa WHERE id = $studentId";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) === 0) {
                $error = 'Siswa tidak ditemukan';
            } else {
                // Check if student has already borrowed books
                $query = "SELECT COUNT(*) as active_loans FROM peminjaman WHERE siswa_id = $studentId AND status = 'dipinjam'";
                $result = mysqli_query($conn, $query);
                $row = mysqli_fetch_assoc($result);
                $currentLoans = (int)$row['active_loans'];
                
                // Check if adding these books would exceed the 3-book limit
                if (($currentLoans + count($bookIds)) > 3) {
                    $error = 'Siswa sudah meminjam ' . $currentLoans . ' buku. Tidak dapat meminjam lebih dari 3 buku secara bersamaan.';
                } else {
                    // Check if student is trying to borrow the same book multiple times
                    if (count($bookIds) !== count(array_unique($bookIds))) {
                        $error = 'Tidak dapat meminjam buku yang sama lebih dari satu kali';
                    } else {
                        // Check if student already has any of these books borrowed
                        $bookIdsStr = implode(',', $bookIds);
                        $query = "SELECT b.judul 
                                 FROM peminjaman p 
                                 JOIN buku b ON p.buku_id = b.id 
                                 WHERE p.siswa_id = $studentId 
                                 AND p.status = 'dipinjam' 
                                 AND p.buku_id IN ($bookIdsStr)";
                        $result = mysqli_query($conn, $query);
                        
                        if (mysqli_num_rows($result) > 0) {
                            $duplicateBooks = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                $duplicateBooks[] = $row['judul'];
                            }
                            $error = 'Siswa sudah meminjam buku berikut: ' . implode(', ', $duplicateBooks);
                        } else {
                            // Check if books exist and are available
                            $query = "SELECT id, judul, jumlah_buku FROM buku WHERE id IN ($bookIdsStr) AND jumlah_buku > 0";
                            $result = mysqli_query($conn, $query);
                            
                            if (mysqli_num_rows($result) !== count($bookIds)) {
                                $error = 'Satu atau beberapa buku tidak tersedia';
                            } else {
                                // Start transaction
                                mysqli_begin_transaction($conn);
                                
                                try {
                                    // Get student name for logging
                                    $query = "SELECT nama FROM siswa WHERE id = $studentId";
                                    $result = mysqli_query($conn, $query);
                                    $student = mysqli_fetch_assoc($result);
                                    $studentName = $student['nama'];
                                    
                                    // Get books information for logging
                                    $query = "SELECT id, judul, jumlah_buku FROM buku WHERE id IN ($bookIdsStr)";
                                    $result = mysqli_query($conn, $query);
                                    $booksInfo = [];
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $booksInfo[$row['id']] = $row;
                                    }
                                    
                                    // Insert loans for all books
                                    foreach ($bookIds as $bookId) {
                                        // Insert new loan
                                        $query = "INSERT INTO peminjaman (siswa_id, buku_id, tanggal_pinjam, lama_pinjam, status) 
                                                  VALUES ($studentId, $bookId, '$borrowDate', $duration, 'dipinjam')";
                                        
                                        if (!mysqli_query($conn, $query)) {
                                            throw new Exception("Gagal menambahkan peminjaman untuk buku ID $bookId: " . mysqli_error($conn));
                                        }
                                        
                                        // Update book quantity
                                        $currentStock = $booksInfo[$bookId]['jumlah_buku'];
                                        $query = "UPDATE buku SET jumlah_buku = jumlah_buku - 1 WHERE id = $bookId";
                                        
                                        if (!mysqli_query($conn, $query)) {
                                            throw new Exception("Gagal memperbarui stok buku ID $bookId: " . mysqli_error($conn));
                                        }
                                        
                                        // Log the stock change
                                        $admin_id = $_SESSION['user_id'] ?? 0;
                                        $bookTitle = $booksInfo[$bookId]['judul'];
                                        $log_message = "Stok buku '$bookTitle' dikurangi (1) karena dipinjam oleh $studentName. Stok sebelumnya: $currentStock, stok terbaru: " . ($currentStock - 1);
                                        
                                        $query = "INSERT INTO activity_log (user_id, activity_type, details, created_at) 
                                                  VALUES ($admin_id, 'stock_update', '" . mysqli_real_escape_string($conn, $log_message) . "', NOW())";
                                        
                                        if (!mysqli_query($conn, $query)) {
                                            throw new Exception("Gagal mencatat aktivitas log: " . mysqli_error($conn));
                                        }
                                    }
                                    
                                    // Commit transaction
                                    mysqli_commit($conn);
                                    
                                    // Set success message
                                    $bookCount = count($bookIds);
                                    $_SESSION['alert'] = "Peminjaman $bookCount buku berhasil ditambahkan";
                                    $_SESSION['alert_type'] = 'success';
                                    
                                    // Redirect to loans page
                                    redirect('peminjaman.php');
                                    
                                } catch (Exception $e) {
                                    // Rollback transaction on error
                                    mysqli_rollback($conn);
                                    $error = $e->getMessage();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!-- Add Loan Form -->
<div class="card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form action="" method="post">
        <div class="form-group">
            <label for="siswa_id">Pilih Siswa <span style="color: red;">*</span></label>
            
            <div style="position: relative;">
                <input type="text" id="student_search" placeholder="Ketik nama atau NISN siswa..." style="width: 100%;">
                <select id="siswa_id" name="siswa_id" required style="display: none;">
                    <option value="">Pilih Siswa</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" data-nisn="<?php echo htmlspecialchars($student['nisn']); ?>" data-nama="<?php echo htmlspecialchars($student['nama']); ?>" data-kelas="<?php echo htmlspecialchars($student['kelas']); ?>">
                            <?php echo htmlspecialchars($student['nama']) . ' (NISN: ' . htmlspecialchars($student['nisn']) . ') - Kelas ' . htmlspecialchars($student['kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div id="student_search_results" style="display: none; position: absolute; z-index: 10; width: 100%; max-height: 300px; overflow-y: auto; background: white; border: 1px solid #ccc; border-top: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <!-- Results will be dynamically populated here -->
                </div>
                
                <div id="selected_student_info" style="margin-top: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; display: none;">
                    <p><strong>Nama:</strong> <span id="info_nama"></span></p>
                    <p><strong>NISN:</strong> <span id="info_nisn"></span></p>
                    <p><strong>Kelas:</strong> <span id="info_kelas"></span></p>
                </div>
            </div>
            
            <?php if (count($students) == 0): ?>
                <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Tidak ada siswa yang ditemukan. Silakan tambahkan siswa baru terlebih dahulu.</p>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Pilih Buku (Maksimal 3) <span style="color: red;">*</span></label>
            
            <div class="category-filter" style="margin-bottom: 15px;">
                <label for="category_filter">Filter berdasarkan Kategori:</label>
                <select id="category_filter" style="width: 200px; margin-left: 10px;">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="book-selection-container">
                <div class="book-selection" style="margin-bottom: 10px; border: 1px solid #eee; padding: 10px; border-radius: 4px;">
                    <select name="buku_id[]" class="book-select" required>
                        <option value="">Pilih Buku</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?php echo $book['id']; ?>" 
                                    data-category="<?php echo $book['kategori_id']; ?>"
                                    <?php echo $book['jumlah_buku'] == 0 ? 'disabled' : ''; ?>
                                    style="<?php echo $book['jumlah_buku'] == 0 ? 'color: #999; font-style: italic;' : ''; ?>">
                                <?php echo htmlspecialchars($book['judul']) . ' (' . $book['jumlah_buku'] . ' tersedia) - ' . htmlspecialchars($book['nama_kategori']); ?>
                                <?php echo $book['jumlah_buku'] == 0 ? ' - Stok Habis' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 10px;">
                <button type="button" id="add-book-btn" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9rem;">
                    <i class="fas fa-plus"></i> Tambah Buku Lain
                </button>
                <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Siswa maksimal boleh meminjam 3 buku secara bersamaan.</p>
            </div>
        </div>
        
        <div class="form-group">
            <label for="tanggal_pinjam">Tanggal Pinjam <span style="color: red;">*</span></label>
            <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="lama_pinjam">Lama Peminjaman (hari) <span style="color: red;">*</span></label>
            <input type="number" id="lama_pinjam" name="lama_pinjam" min="1" max="30" value="7" required>
        </div>
        
        <div class="form-group" style="display: flex; gap: 10px;">
            <button type="submit" name="submit_loan" class="btn">Simpan</button>
            <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<style>
.book-select option:disabled {
    background-color: #f8f9fa;
    color: #999;
    font-style: italic;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSearch = document.getElementById('student_search');
    const studentSelect = document.getElementById('siswa_id');
    const searchResults = document.getElementById('student_search_results');
    const selectedStudentInfo = document.getElementById('selected_student_info');
    const infoNama = document.getElementById('info_nama');
    const infoNisn = document.getElementById('info_nisn');
    const infoKelas = document.getElementById('info_kelas');
    
    // Book selection handling
    const addBookBtn = document.getElementById('add-book-btn');
    const bookSelectionContainer = document.getElementById('book-selection-container');
    const categoryFilter = document.getElementById('category_filter');
    
    // Function to filter books by category
    function filterBooksByCategory() {
        const selectedCategory = categoryFilter.value;
        const bookSelects = document.querySelectorAll('.book-select');
        
        bookSelects.forEach(select => {
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') return; // Skip the default "Pilih Buku" option
                
                if (!selectedCategory || option.dataset.category === selectedCategory) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset selection if current selection is hidden
            if (select.value && select.querySelector(`option[value="${select.value}"]`).style.display === 'none') {
                select.value = '';
            }
        });
    }
    
    // Add category filter change event
    categoryFilter.addEventListener('change', filterBooksByCategory);
    
    // Function to check for duplicate book selections
    function checkDuplicateBooks() {
        const bookSelects = document.querySelectorAll('.book-select');
        const selectedBooks = new Set();
        let hasDuplicate = false;
        
        bookSelects.forEach(select => {
            if (select.value) {
                if (selectedBooks.has(select.value)) {
                    hasDuplicate = true;
                } else {
                    selectedBooks.add(select.value);
                }
            }
        });
        
        // Show error if duplicate found
        const errorMsg = document.getElementById('duplicate-book-error');
        if (hasDuplicate) {
            if (!errorMsg) {
                const error = document.createElement('div');
                error.id = 'duplicate-book-error';
                error.className = 'alert alert-danger';
                error.style.marginTop = '10px';
                error.textContent = 'Tidak dapat meminjam buku yang sama lebih dari satu kali';
                document.querySelector('.book-selection').parentNode.insertBefore(error, document.querySelector('.book-selection'));
            }
            return false;
        } else {
            if (errorMsg) {
                errorMsg.remove();
            }
            return true;
        }
    }
    
    // Add change event to all book selects
    document.querySelectorAll('.book-select').forEach(select => {
        select.addEventListener('change', checkDuplicateBooks);
    });
    
    // Modify add book button click handler
    addBookBtn.addEventListener('click', function() {
        const bookSelections = document.querySelectorAll('.book-selection');
        if (bookSelections.length >= 3) {
            alert('Maksimal 3 buku dapat dipinjam secara bersamaan');
            return;
        }
        
        const newBookSelection = document.createElement('div');
        newBookSelection.className = 'book-selection';
        newBookSelection.style.marginBottom = '10px';
        newBookSelection.style.border = '1px solid #eee';
        newBookSelection.style.padding = '10px';
        newBookSelection.style.borderRadius = '4px';
        
        // Clone the first book select and its options
        const firstBookSelect = document.querySelector('.book-select');
        const newBookSelect = firstBookSelect.cloneNode(true);
        newBookSelect.value = ''; // Reset the selection
        
        // Add change event to new select
        newBookSelect.addEventListener('change', checkDuplicateBooks);
        
        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger';
        removeBtn.style.padding = '5px 10px';
        removeBtn.style.fontSize = '0.9rem';
        removeBtn.style.marginLeft = '10px';
        removeBtn.innerHTML = '<i class="fas fa-times"></i> Hapus';
        removeBtn.onclick = function() {
            newBookSelection.remove();
            checkDuplicateBooks(); // Check again after removing
        };
        
        newBookSelection.appendChild(newBookSelect);
        newBookSelection.appendChild(removeBtn);
        bookSelectionContainer.appendChild(newBookSelection);
        
        // Apply current category filter to the new select
        filterBooksByCategory();
    });
    
    // Add form submit validation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!checkDuplicateBooks()) {
            e.preventDefault();
        }
    });
    
    // Get all student options
    const studentOptions = Array.from(studentSelect.options).slice(1); // Skip the "Pilih Siswa" option
    
    // Handle student search
    studentSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        searchResults.innerHTML = '';
        
        if (searchTerm.length < 1) {
            searchResults.style.display = 'none';
            return;
        }
        
        // Filter students based on search term
        const filteredStudents = studentOptions.filter(option => {
            const nama = option.getAttribute('data-nama').toLowerCase();
            const nisn = option.getAttribute('data-nisn').toLowerCase();
            const kelas = option.getAttribute('data-kelas').toLowerCase();
            
            return nama.includes(searchTerm) || 
                   nisn.includes(searchTerm) || 
                   ('kelas ' + kelas).includes(searchTerm);
        });
        
        if (filteredStudents.length === 0) {
            const noResults = document.createElement('div');
            noResults.classList.add('search-result-item');
            noResults.textContent = 'Tidak ada siswa yang ditemukan';
            noResults.style.padding = '10px';
            noResults.style.color = '#666';
            searchResults.appendChild(noResults);
        } else {
            filteredStudents.forEach(option => {
                const resultItem = document.createElement('div');
                resultItem.classList.add('search-result-item');
                resultItem.textContent = option.textContent;
                resultItem.style.padding = '10px';
                resultItem.style.borderBottom = '1px solid #eee';
                resultItem.style.cursor = 'pointer';
                
                resultItem.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f5f5f5';
                });
                
                resultItem.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'white';
                });
                
                resultItem.addEventListener('click', function() {
                    studentSelect.value = option.value;
                    studentSearch.value = option.textContent;
                    searchResults.style.display = 'none';
                    
                    // Show selected student info
                    infoNama.textContent = option.getAttribute('data-nama');
                    infoNisn.textContent = option.getAttribute('data-nisn');
                    infoKelas.textContent = 'Kelas ' + option.getAttribute('data-kelas');
                    selectedStudentInfo.style.display = 'block';
                });
                
                searchResults.appendChild(resultItem);
            });
        }
        
        searchResults.style.display = 'block';
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target !== studentSearch && event.target !== searchResults) {
            searchResults.style.display = 'none';
        }
    });
    
    // Show search results when focusing on the search input
    studentSearch.addEventListener('focus', function() {
        if (this.value.length > 0) {
            searchResults.style.display = 'block';
        }
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 