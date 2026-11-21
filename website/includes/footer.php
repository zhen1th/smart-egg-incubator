<?php
/**
 * Modern Layout Footer Component
 * Smart Egg Incubator IoT System
 */

declare(strict_types=1);
?>
        </main>

        <!-- Footer: Profil Anggota Kelompok -->
        <footer class="app-footer">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                <div class="d-flex align-items-center gap-2 text-secondary small fw-semibold">
                    <i class="bi bi-people-fill text-amber"></i>
                    <span>Anggota Kelompok:</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="member-chip">Partawijaya Rihal Dariretci <span class="text-secondary">(123240181)</span></span>
                    <span class="member-chip">Yohanes Herang Aji Dharma <span class="text-secondary">(123240191)</span></span>
                    <span class="member-chip">Alvin Andhika Putra <span class="text-secondary">(123240193)</span></span>
                    <span class="member-chip">Kafka Akmal Dani <span class="text-secondary">(123240203)</span></span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Toast Container for Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="appToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i id="toastIcon" class="bi bi-info-circle-fill fs-5"></i>
                <span id="toastMessage">Pemberitahuan sistem.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Chart.js 4.4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
