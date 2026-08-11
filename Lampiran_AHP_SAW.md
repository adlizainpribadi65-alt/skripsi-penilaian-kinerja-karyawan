# Lampiran: Perhitungan Manual Algoritma AHP dan SAW

Lampiran ini berisi detail perhitungan algoritma Analytical Hierarchy Process (AHP) untuk menentukan bobot kriteria, dan Simple Additive Weighting (SAW) untuk penentuan nilai akhir serta perangkingan karyawan berdasarkan data terbaru.

## 1. Perhitungan Bobot Kriteria dengan AHP

### a. Matriks Perbandingan Berpasangan (Pairwise Comparison)
Terdapat 5 kriteria utama yang dinilai:
- **C1** = Presensi (Target Bobot: 10%)
- **C2** = Produktivitas (Target Bobot: 30%)
- **C3** = Disiplin (Target Bobot: 20%)
- **C4** = Kerja Sama (Target Bobot: 15%)
- **C5** = Kualitas Kerja (Target Bobot: 25%)

Untuk menghasilkan konsistensi yang sempurna (identik dengan aplikasi web), matriks perbandingan per kriteria menggunakan rasio matematis presisi antar kriteria:

| Kriteria | C1 (Presensi) | C2 (Produktivitas) | C3 (Disiplin) | C4 (Kerja Sama) | C5 (Kualitas Kerja) |
|:---|:---:|:---:|:---:|:---:|:---:|
| **C1 (Presensi)** | 1 | 0.333 | 0.5 | 0.667 | 0.4 |
| **C2 (Produktivitas)** | 3 | 1 | 1.5 | 2 | 1.2 |
| **C3 (Disiplin)** | 2 | 0.667 | 1 | 1.333 | 0.8 |
| **C4 (Kerja Sama)** | 1.5 | 0.5 | 0.75 | 1 | 0.6 |
| **C5 (Kualitas Kerja)**| 2.5 | 0.833 | 1.25 | 1.667 | 1 |
| **Jumlah Kolom** | **10** | **3.333** | **5** | **6.667** | **4** |

### b. Normalisasi Matriks dan Nilai Prioritas (Bobot)
Normalisasi matriks dilakukan dengan membagi setiap sel dengan jumlah total kolomnya.
*(Contoh sel C1-C1: 1 / 10 = 0.10, sel C2-C1: 3 / 10 = 0.30)*

| Kriteria | C1 | C2 | C3 | C4 | C5 | Total Baris | Bobot (Rata-rata) | Persentase |
|:---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **C1 (Presensi)** | 0.10 | 0.10 | 0.10 | 0.10 | 0.10 | 0.50 | **0.10** | **10%** |
| **C2 (Produktivitas)** | 0.30 | 0.30 | 0.30 | 0.30 | 0.30 | 1.50 | **0.30** | **30%** |
| **C3 (Disiplin)** | 0.20 | 0.20 | 0.20 | 0.20 | 0.20 | 1.00 | **0.20** | **20%** |
| **C4 (Kerja Sama)**| 0.15 | 0.15 | 0.15 | 0.15 | 0.15 | 0.75 | **0.15** | **15%** |
| **C5 (Kualitas Kerja)**| 0.25 | 0.25 | 0.25 | 0.25 | 0.25 | 1.25 | **0.25** | **25%** |

### c. Perhitungan Konsistensi (Consistency Ratio / CR)
Setiap nilai *Consistency Ratio* harus dievaluasi untuk memastikan rasionalitas pembobotan ($\le 0.1$).
Langkah pertama mencari *Weighted Sum Vector (WSV)*, mengalikan matriks awal dengan vektor bobot prioritas.

* **WSV C1** = (1×0.10) + (0.333×0.30) + (0.5×0.20) + (0.667×0.15) + (0.4×0.25) = 0.50
* **WSV C2** = (3×0.10) + (1×0.30) + (1.5×0.20) + (2×0.15) + (1.2×0.25) = 1.50
* **WSV C3** = (2×0.10) + (0.667×0.30) + (1×0.20) + (1.333×0.15) + (0.8×0.25) = 1.00
* **WSV C4** = (1.5×0.10) + (0.5×0.30) + (0.75×0.20) + (1×0.15) + (0.6×0.25) = 0.75
* **WSV C5** = (2.5×0.10) + (0.833×0.30) + (1.25×0.20) + (1.667×0.15) + (1×0.25) = 1.25

**Menghitung Eigenvalue ($\lambda$):** (WSV dibagi Bobot)
* $\lambda_1 = 0.50 / 0.10 = 5$
* $\lambda_2 = 1.50 / 0.30 = 5$
* $\lambda_3 = 1.00 / 0.20 = 5$
* $\lambda_4 = 0.75 / 0.15 = 5$
* $\lambda_5 = 1.25 / 0.25 = 5$

**Maximum Eigenvalue ($\lambda_{max}$):**
$\lambda_{max} = \frac{5 + 5 + 5 + 5 + 5}{5} = \mathbf{5}$

**Consistency Index (CI):**
$n = 5$ (jumlah kriteria)
$CI = \frac{\lambda_{max} - n}{n - 1} = \frac{5 - 5}{4} = \mathbf{0}$

**Consistency Ratio (CR):**
Nilai Random Index (RI) standar untuk matriks ukuran $n=5$ adalah **1.12**.
$CR = \frac{CI}{RI} = \frac{0}{1.12} = \mathbf{0}$

> **Kesimpulan Pengujian AHP:** Karena nilai $CR = 0 \le 0.1$, maka matriks perbandingan memiliki **Konsistensi Sempurna** (Perfect Consistency). Bobot kriteria ini dinyatakan sah dan bisa digunakan di tahap perangkingan SAW.

---

## 2. Perhitungan Simple Additive Weighting (SAW)

Diasumsikan semua kriteria bersifat **Benefit** (Semakin tinggi nilainya, semakin baik pencapaiannya).

### a. Matriks Keputusan (Data Mentah Alternatif) - *X*
Berikut adalah perolehan nilai kinerja mentah masing-masing karyawan (10 karyawan):

| Alternatif (Karyawan) | C1 (Presensi) | C2 (Produktivitas) | C3 (Disiplin) | C4 (Kerja Sama) | C5 (Kualitas Kerja) |
|:---|:---:|:---:|:---:|:---:|:---:|
| **A1** (Putri Regina) | 85 | 90 | 80 | 85 | 90 |
| **A2** (Udi Sepudin) | 80 | 85 | 75 | 80 | 85 |
| **A3** (Pian Sopian) | 90 | 88 | 85 | 90 | 88 |
| **A4** (Wawan S) | 75 | 80 | 80 | 75 | 80 |
| **A5** (Dodi H) | 95 | 95 | 90 | 85 | 95 |
| **A6** (Wati) | 85 | 88 | 85 | 85 | 88 |
| **A7** (M.Umo) | 90 | 85 | 90 | 80 | 85 |
| **A8** (Aca Suryandi) | 80 | 82 | 78 | 85 | 80 |
| **A9** (Aneu R) | 92 | 90 | 88 | 90 | 92 |
| **A10** (Irvan E) | 78 | 85 | 82 | 80 | 85 |
| **Nilai Maksimum ($Max X_j$)** | **95** | **95** | **90** | **90** | **95** |

### b. Matriks Normalisasi - *R*
Menggunakan rumus benefit: $R_{ij} = \frac{X_{ij}}{Max(X_j)}$

*Contoh Perhitungan C1:*
* A1 (Putri) = 85 / 95 = 0.895
* A2 (Udi) = 80 / 95 = 0.842
* A5 (Dodi) = 95 / 95 = 1.000 *(dan seterusnya untuk kolom lain)*

**Hasil Matriks Ternormalisasi (R):**

| Alternatif | C1 (0.10) | C2 (0.30) | C3 (0.20) | C4 (0.15) | C5 (0.25) |
|:---|:---:|:---:|:---:|:---:|:---:|
| **A1** (Putri) | 0.895 | 0.947 | 0.889 | 0.944 | 0.947 |
| **A2** (Udi) | 0.842 | 0.895 | 0.833 | 0.889 | 0.895 |
| **A3** (Pian) | 0.947 | 0.926 | 0.944 | 1.000 | 0.926 |
| **A4** (Wawan) | 0.789 | 0.842 | 0.889 | 0.833 | 0.842 |
| **A5** (Dodi) | 1.000 | 1.000 | 1.000 | 0.944 | 1.000 |
| **A6** (Wati) | 0.895 | 0.926 | 0.944 | 0.944 | 0.926 |
| **A7** (M.Umo) | 0.947 | 0.895 | 1.000 | 0.889 | 0.895 |
| **A8** (Aca S) | 0.842 | 0.863 | 0.867 | 0.944 | 0.842 |
| **A9** (Aneu R) | 0.968 | 0.947 | 0.978 | 1.000 | 0.968 |
| **A10** (Irvan E) | 0.821 | 0.895 | 0.911 | 0.889 | 0.895 |

### c. Perhitungan Nilai Akhir (Preferensi) - *V*
Mengalikan nilai dari normalisasi ($R$) dengan bobot kriteria AHP ($W$): $V_i = \sum (W_j \times R_{ij})$

* **V1 (Putri) :** (0.10 × 0.895) + (0.30 × 0.947) + (0.20 × 0.889) + (0.15 × 0.944) + (0.25 × 0.947) = **0.930**
* **V2 (Udi) :** (0.10 × 0.842) + (0.30 × 0.895) + (0.20 × 0.833) + (0.15 × 0.889) + (0.25 × 0.895) = **0.876**
* **V3 (Pian) :** (0.10 × 0.947) + (0.30 × 0.926) + (0.20 × 0.944) + (0.15 × 1.000) + (0.25 × 0.926) = **0.943**
* **V4 (Wawan) :** (0.10 × 0.789) + (0.30 × 0.842) + (0.20 × 0.889) + (0.15 × 0.833) + (0.25 × 0.842) = **0.845**
* **V5 (Dodi) :** (0.10 × 1.000) + (0.30 × 1.000) + (0.20 × 1.000) + (0.15 × 0.944) + (0.25 × 1.000) = **0.992**
* **V6 (Wati) :** (0.10 × 0.895) + (0.30 × 0.926) + (0.20 × 0.944) + (0.15 × 0.944) + (0.25 × 0.926) = **0.929**
* **V7 (M.Umo) :** (0.10 × 0.947) + (0.30 × 0.895) + (0.20 × 1.000) + (0.15 × 0.889) + (0.25 × 0.895) = **0.920**
* **V8 (Aca S) :** (0.10 × 0.842) + (0.30 × 0.863) + (0.20 × 0.867) + (0.15 × 0.944) + (0.25 × 0.842) = **0.869**
* **V9 (Aneu R) :** (0.10 × 0.968) + (0.30 × 0.947) + (0.20 × 0.978) + (0.15 × 1.000) + (0.25 × 0.968) = **0.969**
* **V10 (Irvan E) :** (0.10 × 0.821) + (0.30 × 0.895) + (0.20 × 0.911) + (0.15 × 0.889) + (0.25 × 0.895) = **0.890**

### d. Hasil Perangkingan Akhir dan Standar Operasional Prosedur (SOP) Tindakan
Dari nilai akhir (V) di atas, berikut adalah hasil perangkingan berserta penerapan tindakan SOP yang relevan untuk seluruh (10) karyawan yang ada di database saat ini:

| Peringkat | Karyawan | Nilai Akhir (V) | Keputusan (SOP Tindakan) |
|:---:|:---|:---:|:---|
| **1** | **Dodi H** (A5) | **0.992** | Diberikan **Reward (Bonus / Promosi)** |
| **2** | **Aneu R** (A9) | **0.969** | Diberikan **Reward (Bonus / Promosi)** |
| **3** | **Pian Sopian** (A3) | **0.943** | Diberikan **Reward (Bonus Kinerja)** |
| **4** | **Putri Regina** (A1) | **0.930** | Kinerja Baik **(Dipertahankan)** |
| **5** | **Wati** (A6) | **0.929** | Kinerja Baik **(Dipertahankan)** |
| **6** | **M.Umo** (A7) | **0.920** | Kinerja Baik **(Dipertahankan)** |
| **7** | **Irvan E** (A10) | **0.890** | Aman / Perlu Peningkatan Sedikit |
| **8** | **Udi Sepudin** (A2) | **0.876** | Memerlukan **Pembinaan & Pelatihan** |
| **9** | **Aca Suryandi** (A8) | **0.869** | Memerlukan **Pembinaan Intensif** |
| **10** | **Wawan S** (A4) | **0.845** | Perlu Tinjauan Kinerja **(Surat Peringatan)** |

*(Catatan: Kategori pembagian standar seperti pemberian Reward > 0.93, Aman 0.88 - 0.93, dan Pembinaan < 0.88 dapat disesuaikan dengan aturan HRD masing-masing perusahan)*
