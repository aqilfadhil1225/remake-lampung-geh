export type LiveEntry = {
  time: string;
  title: string;
  text: string;
};

export type Article = {
  id: string;
  title: string;
  excerpt: string;
  category: string;
  subCategory: string;
  region: string;
  author: string;
  authorInitials: string;
  publishedAt: string;
  fullDate: string;
  readTime: number;
  isHeadline: boolean;
  isLive: boolean;
  isRecommended: boolean;
  isFeatured: boolean;
  hasVideo: boolean;
  videoPlatform?: string;
  image: string;
  caption: string;
  credit: string;
  body: string[];
  liveEntries?: LiveEntry[];
};

const image = (id: string) =>
  `https://images.unsplash.com/${id}?auto=format&fit=crop&w=1200&q=80`;

export const articles: Article[] = [
  {
    id: "art-1",
    title:
      "LIVE: Arus Balik Penyeberangan Pelabuhan Bakauheni Terpantau Padat Lancar Malam Ini",
    excerpt:
      "PT ASDP Indonesia Ferry Cabang Bakauheni mengoperasikan 32 kapal roll-on/roll-off untuk mengurai antrean kendaraan di dermaga eksekutif dan reguler.",
    category: "Peristiwa",
    subCategory: "Lalu Lintas",
    region: "Lampung Selatan",
    author: "Ridho Adwi Pratama",
    authorInitials: "RA",
    publishedAt: "12 menit lalu",
    fullDate: "26 November 2026, 10:42 WIB",
    readTime: 4,
    isHeadline: true,
    isLive: true,
    isRecommended: false,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1544620347-c4fd4a3d5957"),
    caption:
      "Antrean kendaraan roda empat memasuki area parkir dermaga eksekutif Pelabuhan Bakauheni, Lampung Selatan.",
    credit: "Foto: Ridho Adwi / Lampung Geh",
    liveEntries: [
      {
        time: "10:35 WIB",
        title: "Kantong Parkir Dermaga Reguler Terisi 70 Persen",
        text: "Petugas kepolisian bersama ASDP memberlakukan sistem buka-tutup jalur menuju dermaga 1 hingga 3 guna menjaga kelancaran proses muat kapal.",
      },
      {
        time: "09:50 WIB",
        title:
          "Delaying System di Rest Area KM 20B Tol Bakter Diterapkan Situasional",
        text: "Pengendara diimbau memastikan tiket penyeberangan Ferizy sudah dipesan maksimal radius 5 kilometer sebelum area pelabuhan.",
      },
      {
        time: "08:30 WIB",
        title:
          "Cuaca Perairan Selat Sunda Cerah Berawan, Tinggi Gelombang 0,8 Meter",
        text: "BMKG Maritim Panjang melaporkan kondisi angin dan arus laut sangat kondusif untuk pelayaran lintas Bakauheni–Merak.",
      },
    ],
    body: [
      "LAMPUNG SELATAN — Situasi arus lalu lintas kendaraan di kawasan Pelabuhan Penyeberangan Bakauheni, Kabupaten Lampung Selatan, terpantau padat lancar pada Kamis (26/11/2026) pagi hingga menjelang siang.",
      "General Manager PT ASDP Indonesia Ferry Cabang Bakauheni menyatakan pihaknya telah menyiagakan 32 armada kapal dengan pola operasi normal-padat guna memastikan waktu sandar (port time) tetap di bawah 45 menit.",
      "Sementara itu, Satlantas Polres Lampung Selatan menyiagakan puluhan personel di titik persimpangan Jalan Lintas Sumatera (Jalinsum) dan pintu keluar Tol Bakauheni Selatan untuk mengarahkan arus kendaraan logistik dan mobil pribadi.",
    ],
  },
  {
    id: "art-2",
    title:
      "Pemprov Lampung Percepat Rehabilitasi Ruas Jalan Provinsi Penghubung Sentra Pangan Lampung Tengah",
    excerpt:
      "Perbaikan infrastruktur jalan sepanjang 42 kilometer ditargetkan rampung akhir tahun guna menekan biaya logistik hasil panen petani.",
    category: "News",
    subCategory: "Pemerintahan",
    region: "Lampung Tengah",
    author: "Dea Aulia Handayani",
    authorInitials: "DA",
    publishedAt: "38 menit lalu",
    fullDate: "26 November 2026, 10:15 WIB",
    readTime: 5,
    isHeadline: true,
    isLive: false,
    isRecommended: true,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1515162816999-a0c47dc192f7"),
    caption:
      "Pengerjaan pengaspalan dan perkerasan beton pada ruas jalan penghubung sentra pertanian di Lampung Tengah.",
    credit: "Foto: Dok. Dinas BMBK Lampung",
    body: [
      "BANDAR LAMPUNG — Pemerintah Provinsi Lampung mempercepat penyelesaian paket rehabilitasi jalan provinsi yang menghubungkan kawasan sentra produksi padi dan jagung di Kabupaten Lampung Tengah.",
      "Kepala Dinas Bina Marga dan Bina Konstruksi (BMBK) Provinsi Lampung menjelaskan bahwa pengerjaan dilakukan dengan kombinasi konstruksi rigid pavement (beton) pada titik rawan genangan dan pengaspalan hotmix berlapis.",
      "Dengan membaiknya kualitas ruas jalan tersebut, waktu tempuh angkutan hasil pertanian menuju Bandar Lampung dan Pelabuhan Panjang diproyeksikan berkurang hingga 40 menit.",
    ],
  },
  {
    id: "art-3",
    title:
      "Pesona Ombak Tanjung Setia Pesisir Barat Kembali Masuk Destinasi Selancar Favorit Dunia",
    excerpt:
      "Kunjungan wisatawan mancanegara ke Pesisir Barat meningkat 28 persen sepanjang kuartal keempat tahun ini.",
    category: "Wisata & Kuliner",
    subCategory: "Wisata Alam",
    region: "Pesisir Barat",
    author: "Naufal Hakim",
    authorInitials: "NH",
    publishedAt: "1 jam lalu",
    fullDate: "26 November 2026, 09:40 WIB",
    readTime: 4,
    isHeadline: true,
    isLive: false,
    isRecommended: true,
    isFeatured: true,
    hasVideo: false,
    image: image("photo-1502680390469-be75c86f636f"),
    caption:
      "Peselancar menikmati gulungan ombak panjang di Pantai Tanjung Setia, Kabupaten Pesisir Barat.",
    credit: "Foto: Naufal Hakim / Lampung Geh",
    body: [
      "PESISIR BARAT — Pantai Tanjung Setia di Kecamatan Pesisir Selatan, Kabupaten Pesisir Barat, kembali menarik perhatian komunitas peselancar internasional berkat konsistensi ombak Samudra Hindia yang mencapai ketinggian 4 hingga 6 meter.",
      "Pelaku usaha penginapan lokal menyebutkan tingkat okupansi homestay dan resor di sepanjang pesisir Krui mengalami lonjakan signifikan sejak pembukaan rute penerbangan perintis ke Bandara Muhammad Taufiq Kiemas.",
    ],
  },
  {
    id: "art-4",
    title:
      "Ekspor Kopi Robusta Lampung Tembus Pasar Eropa Timur, Petani Liwa Panen Apresiasi Harga",
    excerpt:
      "Penerapan sistem petik merah dan sertifikasi indikasi geografis mendongkrak nilai jual biji kopi asal Lampung Barat.",
    category: "News",
    subCategory: "Ekonomi",
    region: "Lampung Barat",
    author: "Dea Aulia Handayani",
    authorInitials: "DA",
    publishedAt: "2 jam lalu",
    fullDate: "26 November 2026, 08:55 WIB",
    readTime: 5,
    isHeadline: false,
    isLive: false,
    isRecommended: true,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1447933601403-0c6688de566e"),
    caption:
      "Sortasi biji kopi robusta petik merah asal Kecamatan Balik Bukit, Lampung Barat.",
    credit: "Foto: Dok. Koperasi Kopi Liwa",
    body: [
      "LAMPUNG BARAT — Komoditas kopi robusta asal Kabupaten Lampung Barat mencatatkan ekspansi ekspor baru ke sejumlah negara di kawasan Eropa Timur melalui Pelabuhan Panjang.",
      "Gabungan Kelompok Tani (Gapoktan) di kawasan Liwa dan Sekincau kini konsisten menerapkan metode panen petik merah yang menghasilkan profil cita rasa lebih bersih dan kadar air sesuai standar ekspor internasional.",
    ],
  },
  {
    id: "art-5",
    title:
      "Pemkot Bandar Lampung Revitalisasi Koridor Pedestrian Kartini dan Kawasan Kuliner Malam",
    excerpt:
      "Penataan trotoar ramah disabilitas dan pencahayaan artistik diharapkan menghidupkan UMKM lokal di pusat kota.",
    category: "News",
    subCategory: "Pemerintahan",
    region: "Bandar Lampung",
    author: "Ridho Adwi Pratama",
    authorInitials: "RA",
    publishedAt: "3 jam lalu",
    fullDate: "26 November 2026, 07:50 WIB",
    readTime: 3,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1519501025264-65ba15a82390"),
    caption:
      "Ilustrasi penataan jalur pedestrian dan ruang publik perkotaan di Bandar Lampung.",
    credit: "Foto: Ridho Adwi / Lampung Geh",
    body: [
      "BANDAR LAMPUNG — Pemerintah Kota Bandar Lampung mulai menata ulang jalur pedestrian di koridor Jalan Kartini hingga kawasan Tugu Adipura sebagai ruang publik yang nyaman bagi pejalan kaki.",
      "Selain pelebaran trotoar dengan ubin pemandu difabel, pemerintah daerah juga menyiapkan zona khusus bagi pelaku UMKM kuliner malam agar tertata rapi tanpa mengganggu kelancaran lalu lintas.",
    ],
  },
  {
    id: "art-6",
    title:
      "Bhayangkara Presisi Lampung FC Matangkan Taktik Jelang Laga Kandang di Stadion Sumpah Pemuda",
    excerpt:
      "Sesi latihan tertutup difokuskan pada transisi serangan balik cepat dan penyelesaian akhir bola mati.",
    category: "Lifestyle",
    subCategory: "Olahraga",
    region: "Bandar Lampung",
    author: "Bagas Prakoso",
    authorInitials: "BP",
    publishedAt: "3 jam lalu",
    fullDate: "26 November 2026, 07:30 WIB",
    readTime: 3,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1574629810360-7efbbe195018"),
    caption:
      "Suasana sesi latihan tim sepak bola di Stadion Sumpah Pemuda, Way Halim, Bandar Lampung.",
    credit: "Foto: Bagas Prakoso / Lampung Geh",
    body: [
      "BANDAR LAMPUNG — Skuad kebanggaan masyarakat Sai Bumi Ruwa Jurai menjalani latihan intensif di Stadion Sumpah Pemuda, PKOR Way Halim, menjelang pertandingan kandang akhir pekan ini.",
      "Pelatih kepala menegaskan seluruh pemain berada dalam kondisi bugar dan siap mengamankan poin penuh di hadapan ribuan pendukung setia yang memadati tribun.",
    ],
  },
  {
    id: "art-7",
    title:
      "Atlet Angkat Besi Pringsewu Raih Tiga Medali Emas pada Kejuaraan Nasional Junior 2026",
    excerpt:
      "Padepokan Gajah Lampung kembali melahirkan lifter muda berprestasi yang siap diproyeksikan ke pelatnas.",
    category: "Lifestyle",
    subCategory: "Olahraga",
    region: "Pringsewu",
    author: "Bagas Prakoso",
    authorInitials: "BP",
    publishedAt: "4 jam lalu",
    fullDate: "26 November 2026, 06:45 WIB",
    readTime: 4,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1517836357463-d25dfeac3438"),
    caption:
      "Lifter muda asal Kabupaten Pringsewu berlatih di pusat pembinaan angkat besi.",
    credit: "Foto: Dok. KONI Pringsewu",
    body: [
      "PRINGSEWU — Cabang olahraga angkat besi Kabupaten Pringsewu kembali mengharumkan nama Provinsi Lampung setelah menyabet tiga medali emas pada ajang Kejuaraan Nasional Junior 2026.",
      "Prestasi ini memperkuat posisi Pringsewu sebagai lumbung atlet angkat besi nasional yang selama ini dibina secara berkelanjutan di Padepokan Gajah Lampung.",
    ],
  },
  {
    id: "art-8",
    title: "Menjaga Napas Aksara Lampung di Ruang Digital Generasi Z",
    excerpt:
      "Upaya kolaboratif komunitas kreatif di Metro dan Bandar Lampung merancang font digital serta gim edukasi berbasis Aksara Had Lampung.",
    category: "Cerita Opini",
    subCategory: "Feature",
    region: "Metro",
    author: "Siti Rahmawati",
    authorInitials: "SR",
    publishedAt: "5 jam lalu",
    fullDate: "26 November 2026, 06:00 WIB",
    readTime: 6,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: true,
    hasVideo: false,
    image: image("photo-1455390582262-044cdead277a"),
    caption:
      "Dokumentasi lokakarya tipografi dan digitalisasi Aksara Lampung bersama mahasiswa di Kota Metro.",
    credit: "Foto: Siti Rahmawati / Lampung Geh",
    body: [
      "METRO — Di tengah derasnya arus budaya populer global, sekelompok desainer huruf dan pengembang muda di Kota Metro memilih jalan sunyi: menghidupkan kembali Aksara Had Lampung ke dalam ekosistem perangkat lunak modern.",
      "Melalui paket fon berstandar Unicode yang dirilis terbuka, kini pelajar dan kreator konten di seluruh kabupaten/kota Lampung dapat mengetik naskah beraksara Lampung dengan presisi tinggi.",
    ],
  },
  {
    id: "art-9",
    title:
      "VIDEO: Menyusuri Keindahan Pulau Pahawang dan Konservasi Terumbu Karang Pesawaran",
    excerpt:
      "Laporan visual perjalanan laut dari Dermaga Ketapang menuju spot snorkeling Taman Nemo yang jernih.",
    category: "Wisata & Kuliner",
    subCategory: "Wisata Alam",
    region: "Pesawaran",
    author: "Tim Visual Lampung Geh",
    authorInitials: "TV",
    publishedAt: "6 jam lalu",
    fullDate: "26 November 2026, 05:10 WIB",
    readTime: 3,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: true,
    videoPlatform: "YouTube • 04:18",
    image: image("photo-1544551763-46a013bb70d5"),
    caption:
      "Panorama perairan jernih dan gugusan pulau kecil di kawasan wisata Pahawang, Pesawaran.",
    credit: "Video: Tim Visual Lampung Geh",
    body: [
      "PESAWARAN — Hanya berjarak sekitar 45 menit perjalanan dari pusat Kota Bandar Lampung menuju Dermaga Ketapang, Pulau Pahawang di Kabupaten Pesawaran tetap menjadi primadona wisata bahari akhir pekan.",
      "Dalam liputan video kali ini, tim Lampung Geh juga merekam aktivitas kelompok sadar wisata (Pokdarwis) setempat yang rutin melakukan transplantasi terumbu karang.",
    ],
  },
  {
    id: "art-10",
    title:
      "VIDEO: Suasana Panen Raya Udang Vaname di Kawasan Tambak Dipasena Tulang Bawang",
    excerpt:
      "Modernisasi kincir air bertenaga surya membantu petambak menjaga kualitas oksigen kolam.",
    category: "News",
    subCategory: "Berita Foto",
    region: "Tulang Bawang",
    author: "Tim Visual Lampung Geh",
    authorInitials: "TV",
    publishedAt: "7 jam lalu",
    fullDate: "26 November 2026, 04:20 WIB",
    readTime: 3,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: true,
    videoPlatform: "Reels • 01:45",
    image: image("photo-1500382017468-9049fed747ef"),
    caption:
      "Hamparan kawasan pertambakan rakyat di Rawajitu Timur, Kabupaten Tulang Bawang.",
    credit: "Video: Kontributor Tulang Bawang",
    body: [
      "TULANG BAWANG — Petambak udang di kawasan Bumi Dipasena, Kecamatan Rawajitu Timur, Kabupaten Tulang Bawang, menyambut panen raya dengan produktivitas yang stabil.",
      "Dukungan elektrifikasi dan pemanfaatan kincir hemat energi membantu menekan biaya operasional pakan dan listrik secara signifikan.",
    ],
  },
  {
    id: "art-11",
    title:
      "VIDEO: Kuliner Seruit Khas Lampung di Tepi Bendungan Way Rarem Lampung Utara",
    excerpt:
      "Perpaduan sambal terasi bakar, tempoyak durian, dan ikan baung bakar yang menggugah selera.",
    category: "Wisata & Kuliner",
    subCategory: "Rekomendasi Tempat",
    region: "Lampung Utara",
    author: "Naufal Hakim",
    authorInitials: "NH",
    publishedAt: "8 jam lalu",
    fullDate: "26 November 2026, 03:15 WIB",
    readTime: 3,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: true,
    videoPlatform: "TikTok • 02:10",
    image: image("photo-1504674900247-0877df9cc836"),
    caption:
      "Sajian seruit lengkap dengan lalapan segar dan ikan bakar khas Lampung.",
    credit: "Video: Naufal Hakim / Lampung Geh",
    body: [
      "LAMPUNG UTARA — Berkunjung ke Kotabumi dan kawasan Bendungan Way Rarem terasa belum lengkap tanpa mencicipi tradisi nyeruit bersama keluarga.",
      "Sambal rampai segar yang diulek langsung dipadukan dengan ikan sungai bakar dan tempoyak menciptakan harmoni rasa asam, pedas, dan gurih yang autentik.",
    ],
  },
  {
    id: "art-12",
    title:
      "Polres Way Kanan Ungkap Sindikat Pencurian Kendaraan Bermotor Lintas Kabupaten",
    excerpt:
      "Lima unit sepeda motor hasil curian berhasil diamankan dan dikembalikan langsung kepada pemilik sah tanpa dipungut biaya.",
    category: "Kriminal",
    subCategory: "Penangkapan",
    region: "Way Kanan",
    author: "Ridho Adwi Pratama",
    authorInitials: "RA",
    publishedAt: "9 jam lalu",
    fullDate: "26 November 2026, 02:00 WIB",
    readTime: 4,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1589829545856-d10d557cf95f"),
    caption:
      "Konferensi pers pengungkapan kasus tindak pidana pencurian kendaraan bermotor di Mapolres Way Kanan.",
    credit: "Foto: Dok. Humas Polres Way Kanan",
    body: [
      "WAY KANAN — Satuan Reserse Kriminal Polres Way Kanan berhasil membongkar jaringan pelaku pencurian kendaraan bermotor yang kerap beraksi di wilayah Blambangan Umpu dan Baradatu.",
      "Kapolres Way Kanan mengimbau masyarakat untuk selalu menggunakan kunci pengaman tambahan serta segera melapor ke layanan darurat 110 apabila melihat aktivitas mencurigakan.",
    ],
  },
  {
    id: "art-13",
    title:
      "Galeri Foto: Kemeriahan Festival Budaya Tupping di Kalianda Lampung Selatan",
    excerpt:
      "Ratusan seniman menampilkan tarian topeng tradisional bersejarah warisan Pahlawan Nasional Radin Inten II.",
    category: "News",
    subCategory: "Berita Foto",
    region: "Lampung Selatan",
    author: "Siti Rahmawati",
    authorInitials: "SR",
    publishedAt: "10 jam lalu",
    fullDate: "26 November 2026, 01:00 WIB",
    readTime: 3,
    isHeadline: false,
    isLive: false,
    isRecommended: false,
    isFeatured: false,
    hasVideo: false,
    image: image("photo-1533174072545-7a4b6ad7a6c3"),
    caption:
      "Penari Tupping beraksi dalam parade budaya tahunan di Kalianda, Lampung Selatan.",
    credit: "Foto: Siti Rahmawati / Lampung Geh",
    body: [
      "LAMPUNG SELATAN — Tradisi tari topeng Tupping kembali memukau ribuan warga yang memadati lapangan utama Kalianda dalam rangkaian festival budaya daerah.",
      "Tarian sakral yang dulunya menjadi pasukan pengawal rahasia Radin Inten II ini kini menjadi ikon pelestarian seni pertunjukan tradisional di Lampung Selatan.",
    ],
  },
];

export const regions = [
  "Bandar Lampung",
  "Lampung Selatan",
  "Metro",
  "Pesawaran",
  "Pringsewu",
  "Tanggamus",
  "Lampung Tengah",
  "Lampung Timur",
  "Lampung Utara",
  "Way Kanan",
  "Tulang Bawang",
  "Tulang Bawang Barat",
  "Mesuji",
  "Lampung Barat",
  "Pesisir Barat",
];

export const categories = [
  { name: "News", description: "Ekonomi, Foto, Pemda" },
  { name: "Peristiwa", description: "Bencana, Lalu Lintas" },
  { name: "Kriminal", description: "Hukum & Kriminalitas" },
  { name: "Cerita Opini", description: "Feature & Esai Warga" },
  { name: "Lifestyle", description: "Milenial & Olahraga" },
  { name: "Wisata & Kuliner", description: "Alam, Budaya, Hotel" },
];
