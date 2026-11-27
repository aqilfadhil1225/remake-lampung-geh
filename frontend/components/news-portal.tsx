"use client";

import Image from "next/image";
import {
  useEffect,
  useMemo,
  useState,
  useSyncExternalStore,
  type FormEvent,
} from "react";
import { articles, categories, regions, type Article } from "@/lib/articles";

const bookmarkListeners = new Set<() => void>();
const defaultBookmarks = '["art-2"]';

function subscribeToBookmarks(listener: () => void) {
  bookmarkListeners.add(listener);
  window.addEventListener("storage", listener);
  return () => {
    bookmarkListeners.delete(listener);
    window.removeEventListener("storage", listener);
  };
}

function getBookmarkSnapshot() {
  return window.localStorage.getItem("lgn_bookmarks") ?? defaultBookmarks;
}

function getServerBookmarkSnapshot() {
  return defaultBookmarks;
}

function saveBookmarks(ids: string[]) {
  window.localStorage.setItem("lgn_bookmarks", JSON.stringify(ids));
  bookmarkListeners.forEach((listener) => listener());
}

function parseBookmarks(snapshot: string): string[] {
  try {
    const parsed: unknown = JSON.parse(snapshot);
    if (
      Array.isArray(parsed) &&
      parsed.every((value): value is string => typeof value === "string")
    ) {
      return parsed;
    }
  } catch (error) {
    console.error("Could not read saved Lampung Geh articles.", error);
  }
  return [];
}

function BookmarkIcon({ filled = false }: { filled?: boolean }) {
  return (
    <svg
      aria-hidden="true"
      className="h-4 w-4"
      fill={filled ? "currentColor" : "none"}
      stroke="currentColor"
      strokeWidth="2.1"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"
      />
    </svg>
  );
}

function SearchIcon() {
  return (
    <svg
      aria-hidden="true"
      className="h-5 w-5"
      fill="none"
      stroke="currentColor"
      strokeWidth="2.2"
      viewBox="0 0 24 24"
    >
      <circle cx="11" cy="11" r="8" />
      <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35" />
    </svg>
  );
}

function SectionTitle({
  children,
  accent = "bg-brand",
}: {
  children: React.ReactNode;
  accent?: string;
}) {
  return (
    <div className="mb-5 flex items-center justify-between border-b border-border-subtle pb-3">
      <div className="flex items-center gap-2.5">
        <span className={`h-6 w-1.5 rounded-full ${accent}`} />
        <h2 className="text-[22px] font-extrabold tracking-tight text-heading">
          {children}
        </h2>
      </div>
    </div>
  );
}

function StandardCard({
  article,
  saved,
  onOpen,
  onBookmark,
}: {
  article: Article;
  saved: boolean;
  onOpen: (article: Article) => void;
  onBookmark: (article: Article) => void;
}) {
  const isOpinion = article.category === "Cerita Opini";

  return (
    <article className="group flex flex-col justify-between overflow-hidden rounded-xl border border-border-subtle bg-canvas transition hover:shadow-md">
      <div>
        <button
          aria-label={`Buka berita: ${article.title}`}
          className="relative block aspect-[16/10] w-full cursor-pointer overflow-hidden bg-surface text-left"
          onClick={() => onOpen(article)}
          type="button"
        >
          <Image
            alt={article.title}
            className="object-cover transition-transform duration-300 group-hover:scale-105"
            fill
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
            src={article.image}
          />
          {article.isLive ? (
            <span className="absolute left-2.5 top-2.5 flex items-center gap-1 rounded bg-brand px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-white shadow">
              <span className="live-dot h-1.5 w-1.5 rounded-full bg-white" />
              LIVE
            </span>
          ) : null}
          <span className="absolute bottom-2.5 left-2.5 rounded bg-canvas/95 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-accent">
            {article.region}
          </span>
        </button>
        <div className="p-4">
          <div className="mb-1.5 flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-accent">
            <span>{article.subCategory || article.category}</span>
          </div>
          <button
            className={`cursor-pointer text-left text-[17px] font-extrabold leading-snug text-heading transition group-hover:text-brand ${
              isOpinion ? "font-serif" : "font-sans"
            }`}
            onClick={() => onOpen(article)}
            type="button"
          >
            {article.title}
          </button>
        </div>
      </div>
      <div className="flex items-center justify-between border-t border-border-subtle/70 px-4 pb-3.5 pt-2 text-xs text-muted">
        <div className="flex items-center gap-1.5">
          <span>{article.publishedAt}</span>
          <span>•</span>
          <span>{article.readTime} mnt</span>
        </div>
        <button
          aria-label={saved ? "Hapus dari berita tersimpan" : "Simpan berita"}
          className={`rounded-lg p-1.5 transition hover:bg-surface ${
            saved ? "text-brand" : "text-muted"
          }`}
          onClick={() => onBookmark(article)}
          title="Simpan Berita"
          type="button"
        >
          <BookmarkIcon filled={saved} />
        </button>
      </div>
    </article>
  );
}

function StoryListCard({
  article,
  onOpen,
}: {
  article: Article;
  onOpen: (article: Article) => void;
}) {
  return (
    <article
      className="group flex cursor-pointer items-start gap-3.5 border-b border-border-subtle pb-4 last:border-0 last:pb-0"
      onClick={() => onOpen(article)}
    >
      <Image
        alt={article.title}
        className="h-20 w-24 shrink-0 rounded-lg object-cover"
        height={80}
        src={article.image}
        width={96}
      />
      <div className="min-w-0 flex-1">
        <span className="text-[10px] font-extrabold uppercase tracking-wider text-brand">
          {article.region}
        </span>
        <h3 className="mt-0.5 line-clamp-2 text-sm font-extrabold leading-snug text-heading transition group-hover:text-brand">
          {article.title}
        </h3>
        <span className="mt-1 block text-[11px] text-muted">
          {article.publishedAt} • {article.readTime} mnt
        </span>
      </div>
    </article>
  );
}

function LatestPost({
  article,
  saved,
  onOpen,
  onBookmark,
}: {
  article: Article;
  saved: boolean;
  onOpen: (article: Article) => void;
  onBookmark: (article: Article) => void;
}) {
  return (
    <article className="group flex flex-col gap-4 rounded-xl border border-border-subtle bg-canvas p-4 transition hover:border-heading/30 sm:flex-row">
      <button
        aria-label={`Buka berita: ${article.title}`}
        className="relative aspect-[16/10] shrink-0 cursor-pointer overflow-hidden rounded-lg bg-surface text-left sm:w-56"
        onClick={() => onOpen(article)}
        type="button"
      >
        <Image
          alt={article.title}
          className="object-cover transition-transform duration-300 group-hover:scale-105"
          fill
          sizes="(max-width: 640px) 100vw, 224px"
          src={article.image}
        />
        {article.hasVideo ? (
          <span className="absolute bottom-2 left-2 rounded bg-brand px-1.5 py-0.5 text-[10px] font-extrabold text-white">
            VIDEO
          </span>
        ) : null}
      </button>
      <div className="flex min-w-0 flex-1 flex-col justify-between">
        <div>
          <div className="mb-1 flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider">
            <span className="text-accent">{article.category}</span>
            <span className="text-border-subtle">•</span>
            <span className="text-muted">{article.region}</span>
          </div>
          <button
            className="cursor-pointer text-left text-lg font-extrabold leading-snug text-heading transition group-hover:text-brand"
            onClick={() => onOpen(article)}
            type="button"
          >
            {article.title}
          </button>
          <p className="mt-1.5 line-clamp-2 text-xs text-body sm:text-sm">
            {article.excerpt}
          </p>
        </div>
        <div className="mt-3 flex items-center justify-between border-t border-border-subtle/70 pt-2.5 text-xs text-muted">
          <span>
            {article.author} • {article.publishedAt} • {article.readTime} mnt baca
          </span>
          <button
            aria-label={saved ? "Hapus dari berita tersimpan" : "Simpan berita"}
            className={`rounded-lg p-1.5 hover:bg-surface ${
              saved ? "text-brand" : "text-muted"
            }`}
            onClick={() => onBookmark(article)}
            title="Simpan Berita"
            type="button"
          >
            <BookmarkIcon filled={saved} />
          </button>
        </div>
      </div>
    </article>
  );
}

export function NewsPortal() {
  const [heroIndex, setHeroIndex] = useState(0);
  const bookmarkSnapshot = useSyncExternalStore(
    subscribeToBookmarks,
    getBookmarkSnapshot,
    getServerBookmarkSnapshot,
  );
  const bookmarks = useMemo(
    () => parseBookmarks(bookmarkSnapshot),
    [bookmarkSnapshot],
  );
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchValue, setSearchValue] = useState("");
  const [filterLabel, setFilterLabel] = useState("");
  const [filteredArticles, setFilteredArticles] = useState<Article[] | null>(null);
  const [activeArticle, setActiveArticle] = useState<Article | null>(null);
  const [bookmarkDrawer, setBookmarkDrawer] = useState(false);
  const [mobileMenu, setMobileMenu] = useState(false);
  const [staticModal, setStaticModal] = useState<"about" | "pedoman" | null>(
    null,
  );
  const [toast, setToast] = useState("");
  const [latestLimit, setLatestLimit] = useState(5);

  const headlines = useMemo(
    () => articles.filter((article) => article.isHeadline),
    [],
  );
  const hero = headlines[heroIndex] ?? headlines[0];
  const savedArticles = articles.filter((article) =>
    bookmarks.includes(article.id),
  );

  useEffect(() => {
    if (!toast) return;
    const timeout = window.setTimeout(() => setToast(""), 2800);
    return () => window.clearTimeout(timeout);
  }, [toast]);

  function showMessage(message: string) {
    setToast(message);
  }

  function toggleBookmark(article: Article) {
    const isSaved = bookmarks.includes(article.id);
    saveBookmarks(
      isSaved
        ? bookmarks.filter((id) => id !== article.id)
        : [article.id, ...bookmarks],
    );
    showMessage(
      isSaved
        ? "Dihapus dari daftar berita tersimpan"
        : "Berita disimpan ke daftar baca Anda",
    );
  }

  function applyFilter(label: string, predicate: (article: Article) => boolean) {
    setFilterLabel(label);
    setFilteredArticles(articles.filter(predicate));
    setActiveArticle(null);
    setSearchOpen(false);
    setMobileMenu(false);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function filterByCategory(category: string) {
    applyFilter(
      `Rubrik: ${category}`,
      (article) => article.category.toLowerCase() === category.toLowerCase(),
    );
  }

  function filterBySubCategory(subCategory: string) {
    applyFilter(
      `Sub-Kategori: ${subCategory}`,
      (article) =>
        article.subCategory.toLowerCase() === subCategory.toLowerCase(),
    );
  }

  function filterByRegion(region: string) {
    applyFilter(
      `Daerah: ${region}`,
      (article) => article.region.toLowerCase() === region.toLowerCase(),
    );
  }

  function resetHome() {
    setFilteredArticles(null);
    setFilterLabel("");
    setActiveArticle(null);
    setSearchValue("");
    setSearchOpen(false);
    setMobileMenu(false);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function submitSearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const query = searchValue.trim();
    if (!query) return;
    const normalized = query.toLowerCase();
    applyFilter(`Pencarian: "${query}"`, (article) =>
      [
        article.title,
        article.excerpt,
        article.region,
        article.category,
      ].some((value) => value.toLowerCase().includes(normalized)),
    );
  }

  function openArticle(article: Article) {
    setActiveArticle(article);
    setFilteredArticles(null);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function shareArticle(platform: "whatsapp" | "x") {
    if (!activeArticle) return;
    const title = encodeURIComponent(`${activeArticle.title} — Lampung Geh News`);
    const url = encodeURIComponent(window.location.href);
    const shareUrl =
      platform === "whatsapp"
        ? `https://api.whatsapp.com/send?text=${title}%20${url}`
        : `https://twitter.com/intent/tweet?text=${title}&url=${url}`;
    window.open(shareUrl, "_blank", "noopener,noreferrer");
  }

  async function copyArticleLink() {
    await navigator.clipboard.writeText(window.location.href);
    showMessage("Tautan artikel berhasil disalin ke clipboard");
  }

  function closeDrawers() {
    setBookmarkDrawer(false);
    setMobileMenu(false);
  }

  function renderHeader() {
    const desktopLinks = [
      "Peristiwa",
      "Kriminal",
      "Cerita Opini",
      "Lifestyle",
      "Wisata & Kuliner",
    ];

    return (
      <>
        <div className="h-9 border-b border-black/20 bg-heading text-xs text-white sm:text-[13px]">
          <div className="mx-auto flex h-full max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
            <div className="flex min-w-0 flex-1 items-center gap-3">
              <span className="flex shrink-0 items-center gap-1.5 rounded bg-brand px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider">
                <span className="live-dot h-1.5 w-1.5 rounded-full bg-white" />
                BREAKING
              </span>
              <button
                className="cursor-pointer truncate text-left font-medium text-white/95 hover:underline"
                onClick={() => openArticle(articles.find((item) => item.isLive)!)}
                type="button"
              >
                LIVE: Arus Balik Penyeberangan Pelabuhan Bakauheni Terpantau
                Padat Lancar Malam Ini
              </button>
            </div>
            <div className="hidden shrink-0 items-center gap-4 text-xs text-white/75 md:flex">
              <span>Kamis, 26 November 2026</span>
              <span className="text-white/30">|</span>
              <button onClick={resetHome} type="button">
                Redaksi
              </button>
              <button onClick={() => setStaticModal("pedoman")} type="button">
                Pedoman Media
              </button>
            </div>
          </div>
        </div>

        <header className="sticky top-0 z-40 border-b border-border-subtle bg-canvas/95 backdrop-blur-md">
          <div className="mx-auto max-w-7xl px-4 sm:px-6">
            <div className="flex h-16 items-center justify-between gap-4 sm:h-[72px]">
              <div className="flex items-center gap-3">
                <button
                  aria-label="Buka Menu"
                  className="-ml-2 rounded-lg p-2 text-heading transition hover:bg-surface lg:hidden"
                  onClick={() => setMobileMenu(true)}
                  type="button"
                >
                  <svg
                    className="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2.2"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M4 6h16M4 12h16M4 18h12"
                    />
                  </svg>
                </button>
                <button
                  className="group flex items-center gap-2.5 text-left"
                  onClick={resetHome}
                  type="button"
                >
                  <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-lg font-black tracking-tighter text-white shadow-sm transition group-hover:bg-brand-dark sm:h-10 sm:w-10 sm:text-xl">
                    LG
                  </span>
                  <span className="flex flex-col leading-none">
                    <span className="text-lg font-black tracking-tight text-heading sm:text-[21px]">
                      LAMPUNG <span className="text-brand">GEH</span>
                    </span>
                    <span className="mt-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-muted sm:text-[11px]">
                      NEWS PORTAL
                    </span>
                  </span>
                </button>
              </div>

              <nav className="hidden h-full items-center gap-1 lg:flex">
                <button
                  className="rounded-lg bg-brand/5 px-3.5 py-2 text-[15px] font-bold text-brand"
                  onClick={resetHome}
                  type="button"
                >
                  Home
                </button>
                <div className="nav-item-news relative flex h-full items-center">
                  <button
                    className="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[15px] font-bold text-heading transition hover:bg-surface hover:text-brand"
                    onClick={() => filterByCategory("News")}
                    type="button"
                  >
                    News
                    <svg
                      className="h-4 w-4 text-muted"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2.2"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="m6 9 6 6 6-6"
                      />
                    </svg>
                  </button>
                  <div className="mega-menu-panel fixed left-0 right-0 top-[108px] z-50 border-b border-border-subtle border-t-2 border-t-brand bg-canvas shadow-xl">
                    <div className="mx-auto grid max-w-7xl grid-cols-12 gap-6 px-6 py-6">
                      <div className="col-span-3 flex flex-col justify-between border-r border-border-subtle pr-5">
                        <div>
                          <p className="mb-3 text-[11px] font-extrabold uppercase tracking-wider text-muted">
                            Sub-Kategori News
                          </p>
                          <ul className="space-y-1">
                            {["Pemerintahan", "Ekonomi", "Berita Foto"].map(
                              (subCategory) => (
                                <li key={subCategory}>
                                  <button
                                    className={`w-full rounded-lg px-3.5 py-2.5 text-left text-sm font-bold ${
                                      megaSub === subCategory
                                        ? "bg-surface text-brand"
                                        : "text-heading hover:bg-surface"
                                    }`}
                                    onMouseEnter={() =>
                                      setMegaSub(subCategory)
                                    }
                                    onClick={() =>
                                      filterBySubCategory(subCategory)
                                    }
                                    type="button"
                                  >
                                    {subCategory}
                                  </button>
                                </li>
                              ),
                            )}
                          </ul>
                        </div>
                        <button
                          className="mt-4 text-left text-xs font-extrabold text-accent hover:underline"
                          onClick={() => filterByCategory("News")}
                          type="button"
                        >
                          Lihat Semua Rubrik News →
                        </button>
                      </div>
                      <div className="col-span-9">
                        <div className="mb-3.5 flex items-center justify-between">
                          <span className="text-xs font-extrabold uppercase tracking-wider text-accent">
                            Terbaru di {megaSub}
                          </span>
                          <span className="text-xs text-muted">
                            4 Berita Terkurasi
                          </span>
                        </div>
                        <div className="grid grid-cols-4 gap-4">
                          {getMegaMenuArticles(megaSub).map((article) => (
                              <button
                                className="group cursor-pointer text-left"
                                key={article.id}
                                onClick={() => openArticle(article)}
                                type="button"
                              >
                                <div className="relative mb-2 aspect-[16/10] overflow-hidden rounded-lg border border-border-subtle bg-surface">
                                  <Image
                                    alt={article.title}
                                    className="object-cover transition-transform duration-300 group-hover:scale-105"
                                    fill
                                    sizes="25vw"
                                    src={article.image}
                                  />
                                </div>
                                <span className="text-[10px] font-extrabold uppercase text-accent">
                                  {article.region}
                                </span>
                                <span className="mt-0.5 line-clamp-2 block text-xs font-extrabold leading-snug text-heading group-hover:text-brand">
                                  {article.title}
                                </span>
                              </button>
                            ))}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                {desktopLinks.map((category) => (
                  <button
                    className="rounded-lg px-3.5 py-2 text-[15px] font-bold text-heading transition hover:bg-surface hover:text-brand"
                    key={category}
                    onClick={() => filterByCategory(category)}
                    type="button"
                  >
                    {category}
                  </button>
                ))}
              </nav>

              <div className="flex items-center gap-2">
                <button
                  aria-label="Berita Tersimpan"
                  className="relative flex items-center gap-2 rounded-lg bg-surface p-2.5 text-heading transition hover:bg-surface-elevated"
                  onClick={() => setBookmarkDrawer(true)}
                  type="button"
                >
                  <BookmarkIcon />
                  <span className="hidden text-xs font-bold sm:inline">
                    Tersimpan
                  </span>
                  <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1.5 text-[11px] font-extrabold text-white">
                    {bookmarks.length}
                  </span>
                </button>
                <button
                  aria-label="Pencarian"
                  className="rounded-lg bg-surface p-2.5 text-heading transition hover:bg-surface-elevated"
                  onClick={() => setSearchOpen((value) => !value)}
                  type="button"
                >
                  <SearchIcon />
                </button>
              </div>
            </div>

            {searchOpen ? (
              <div className="border-t border-border-subtle pb-4 pt-3">
                <form
                  className="flex flex-col gap-2.5 sm:flex-row"
                  onSubmit={submitSearch}
                >
                  <div className="relative flex-1">
                    <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                      <SearchIcon />
                    </span>
                    <input
                      autoFocus
                      className="w-full rounded-lg border border-border-subtle bg-surface py-2.5 pl-10 pr-4 text-sm text-heading transition placeholder:text-muted focus:border-accent focus:bg-canvas focus:outline-none"
                      onChange={(event) => setSearchValue(event.target.value)}
                      placeholder="Cari berita Lampung, kebijakan pemerintahan, wisata, atau tag daerah..."
                      value={searchValue}
                    />
                  </div>
                  <div className="flex gap-2">
                    <button
                      className="rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark"
                      type="submit"
                    >
                      Cari Berita
                    </button>
                    <button
                      className="rounded-lg bg-surface px-3.5 py-2.5 text-sm font-semibold text-heading transition hover:bg-surface-elevated"
                      onClick={() => setSearchOpen(false)}
                      type="button"
                    >
                      Tutup
                    </button>
                  </div>
                </form>
              </div>
            ) : null}
          </div>
        </header>
      </>
    );
  }

  function setMegaSub(subCategory: string) {
    setMegaSubState(subCategory);
  }

  const [megaSub, setMegaSubState] = useState("Pemerintahan");

  function getMegaMenuArticles(subCategory: string) {
    let matching = articles.filter(
      (article) => article.subCategory === subCategory,
    );
    if (matching.length < 4) {
      const additionalNews = articles.filter(
        (article) =>
          article.category === "News" && !matching.includes(article),
      );
      matching = [...matching, ...additionalNews, ...articles].slice(0, 4);
    }
    return matching.slice(0, 4);
  }

  function renderArticleDetail(article: Article) {
    const related = articles.filter((item) => item.id !== article.id).slice(0, 4);

    return (
      <section className="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-border-subtle pb-4">
          <button
            className="inline-flex items-center gap-2 text-sm font-extrabold text-heading transition hover:text-brand"
            onClick={() => setActiveArticle(null)}
            type="button"
          >
            ← Kembali ke Beranda
          </button>
          <nav
            aria-label="Breadcrumb"
            className="flex items-center gap-1.5 text-xs font-medium text-muted"
          >
            <button onClick={resetHome} type="button">
              Home
            </button>
            <span>/</span>
            <span className="font-bold text-accent">{article.category}</span>
            <span>/</span>
            <span>{article.region}</span>
          </nav>
        </div>

        <div className="grid grid-cols-1 gap-10 lg:grid-cols-12">
          <article className="max-w-[720px] lg:col-span-8">
            <div className="mb-3.5 flex flex-wrap items-center gap-2">
              {article.isLive ? (
                <span className="flex items-center gap-1.5 rounded bg-brand px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-white">
                  <span className="live-dot h-2 w-2 rounded-full bg-white" />
                  LIVE REPORT
                </span>
              ) : null}
              <span className="rounded bg-accent px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-white">
                {article.subCategory}
              </span>
              <span className="rounded border border-border-subtle bg-surface px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-accent">
                {article.region}
              </span>
            </div>
            <h1 className="text-[26px] font-extrabold leading-[1.22] tracking-tight text-heading sm:text-[36px]">
              {article.title}
            </h1>
            <div className="mt-5 flex flex-wrap items-center justify-between gap-4 border-y border-border-subtle py-4">
              <div className="flex items-center gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent text-sm font-extrabold text-white">
                  {article.authorInitials}
                </span>
                <div>
                  <p className="text-sm font-extrabold text-heading">
                    {article.author}
                  </p>
                  <p className="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted">
                    <span>{article.fullDate}</span>
                    <span>•</span>
                    <span className="font-semibold text-accent">
                      {article.readTime} menit baca
                    </span>
                  </p>
                </div>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <button
                  className="rounded-lg bg-surface px-3 py-2 text-xs font-bold text-heading transition hover:bg-[#25D366] hover:text-white"
                  onClick={() => shareArticle("whatsapp")}
                  type="button"
                >
                  WhatsApp
                </button>
                <button
                  className="rounded-lg bg-surface px-3 py-2 text-xs font-bold text-heading transition hover:bg-heading hover:text-white"
                  onClick={() => shareArticle("x")}
                  type="button"
                >
                  X
                </button>
                <button
                  className="rounded-lg bg-surface px-3 py-2 text-xs font-bold text-heading transition hover:bg-surface-elevated"
                  onClick={() => void copyArticleLink()}
                  type="button"
                >
                  Salin Link
                </button>
                <button
                  aria-label="Simpan Berita"
                  className="rounded-lg bg-surface p-2 text-heading transition hover:bg-brand hover:text-white"
                  onClick={() => toggleBookmark(article)}
                  type="button"
                >
                  <BookmarkIcon filled={bookmarks.includes(article.id)} />
                </button>
              </div>
            </div>

            <figure className="my-6">
              <div className="relative aspect-video overflow-hidden rounded-xl border border-border-subtle bg-surface">
                <Image
                  alt={article.title}
                  className="object-cover"
                  fill
                  sizes="(max-width: 1024px) 100vw, 720px"
                  src={article.image}
                />
              </div>
              <figcaption className="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-surface px-3.5 py-2.5 text-[13px] text-muted">
                <span>{article.caption}</span>
                <span className="font-bold text-heading">{article.credit}</span>
              </figcaption>
            </figure>

            {article.liveEntries ? (
              <div className="my-8 overflow-hidden rounded-xl border-2 border-brand">
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-brand/20 bg-[#FEECEB] px-5 py-3.5">
                  <span className="flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-brand">
                    <span className="live-dot h-2.5 w-2.5 rounded-full bg-brand" />
                    Liputan Langsung (Live Timeline)
                  </span>
                  <span className="text-xs font-semibold text-heading">
                    Pembaruan kronologis terbaru di atas
                  </span>
                </div>
                <div className="space-y-6 p-5 sm:p-6">
                  {article.liveEntries.map((entry) => (
                    <div
                      className="relative border-l-2 border-border-subtle pl-6"
                      key={entry.time}
                    >
                      <span className="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-brand ring-4 ring-[#FEECEB]" />
                      <span className="mb-1 inline-block rounded bg-brand/10 px-2 py-0.5 text-xs font-extrabold text-brand">
                        {entry.time}
                      </span>
                      <h2 className="text-base font-extrabold leading-snug text-heading">
                        {entry.title}
                      </h2>
                      <p className="mt-1 text-sm leading-relaxed text-body">
                        {entry.text}
                      </p>
                    </div>
                  ))}
                </div>
              </div>
            ) : null}

            <div className="article-prose mt-6">
              {article.body.map((paragraph) => (
                <p key={paragraph}>{paragraph}</p>
              ))}
            </div>
            <div className="mt-8 flex flex-wrap items-center gap-2 border-t border-border-subtle pt-5">
              <span className="mr-1 text-xs font-extrabold uppercase tracking-wider text-muted">
                Topik &amp; Wilayah:
              </span>
              <button
                className="rounded-lg bg-surface px-3 py-1 text-xs font-bold text-heading transition hover:bg-accent hover:text-white"
                onClick={() => filterByRegion(article.region)}
                type="button"
              >
                #{article.region.replace(/\s+/g, "")}
              </button>
              <button
                className="rounded-lg bg-surface px-3 py-1 text-xs font-bold text-heading transition hover:bg-accent hover:text-white"
                onClick={() => filterByCategory(article.category)}
                type="button"
              >
                #{article.category.replace(/\s+/g, "")}
              </button>
              <span className="rounded-lg bg-surface px-3 py-1 text-xs font-bold text-muted">
                #LampungGehNews
              </span>
            </div>

            <section className="mt-10 border-t-2 border-heading pt-8">
              <div className="mb-6 flex items-center justify-between">
                <h2 className="text-xl font-extrabold text-heading">
                  Diskusi Pembaca (2)
                </h2>
                <span className="text-xs font-medium text-muted">
                  Komentar tamu tanpa login
                </span>
              </div>
              <div className="mb-8 rounded-xl border border-border-subtle bg-surface p-5 sm:p-6">
                <h3 className="mb-2 text-base font-extrabold text-heading">
                  Leave a Reply
                </h3>
                <p className="mb-4 text-xs text-muted">
                  Alamat email Anda tidak akan dipublikasikan. Kolom wajib
                  ditandai dengan <span className="font-bold text-brand">*</span>
                </p>
                <form
                  className="space-y-4"
                  onSubmit={(event) => {
                    event.preventDefault();
                    showMessage("Komentar berhasil dikirim ke diskusi pembaca");
                  }}
                >
                  <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                    {["Nama", "Email (Privat)", "Website (Opsional)"].map(
                      (label) => (
                        <label
                          className="text-xs font-bold text-heading"
                          key={label}
                        >
                          {label}
                          <input
                            className="mt-1 block w-full rounded-lg border border-border-subtle bg-canvas px-3.5 py-2.5 text-sm font-normal text-heading focus:border-accent focus:outline-none"
                            placeholder={
                              label.startsWith("Email")
                                ? "nama@email.com"
                                : label.startsWith("Website")
                                  ? "https://"
                                  : "Nama Anda"
                            }
                            required={!label.includes("Opsional")}
                            type={
                              label.startsWith("Email")
                                ? "email"
                                : label.startsWith("Website")
                                  ? "url"
                                  : "text"
                            }
                          />
                        </label>
                      ),
                    )}
                  </div>
                  <label className="block text-xs font-bold text-heading">
                    Komentar
                    <textarea
                      className="mt-1 block w-full rounded-lg border border-border-subtle bg-canvas px-3.5 py-2.5 text-sm font-normal text-heading focus:border-accent focus:outline-none"
                      placeholder="Tulis tanggapan Anda secara santun dan konstruktif..."
                      required
                      rows={3}
                    />
                  </label>
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <span className="text-[11px] text-muted">
                      Dilindungi anti-spam &amp; moderasi redaksi
                    </span>
                    <button
                      className="rounded-lg bg-brand px-5 py-2.5 text-sm font-extrabold text-white transition hover:bg-brand-dark"
                      type="submit"
                    >
                      Kirim Komentar
                    </button>
                  </div>
                </form>
              </div>
              <div className="space-y-5">
                <div className="rounded-xl border border-border-subtle bg-canvas p-4 sm:p-5">
                  <div className="flex items-center gap-2.5">
                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-elevated text-xs font-extrabold text-heading">
                      AF
                    </span>
                    <div>
                      <p className="text-sm font-extrabold text-heading">
                        Ahmad Fauzi
                      </p>
                      <p className="text-[11px] text-muted">
                        26 Nov 2026 • 10:22 WIB
                      </p>
                    </div>
                  </div>
                  <p className="mt-3 text-sm leading-relaxed text-body">
                    Terima kasih informasinya Lampung Geh, sangat membantu buat
                    rencana perjalanan pagi ini. Tampilan web barunya juga
                    bersih banget tanpa iklan pop-up!
                  </p>
                  <div className="ml-4 mt-4 space-y-3 border-l-2 border-border-subtle pl-4 sm:ml-8">
                    <div className="rounded-lg bg-surface p-3.5">
                      <p className="text-xs font-extrabold text-heading">
                        Redaksi Lampung Geh{" "}
                        <span className="rounded bg-brand px-2 py-0.5 text-[10px] uppercase text-white">
                          Redaksi
                        </span>
                      </p>
                      <p className="mt-1.5 text-xs leading-relaxed text-body sm:text-sm">
                        Terima kasih kembali Bang Ahmad. Hati-hati di jalan dan
                        selamat beraktivitas.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </article>

          <aside className="space-y-6 lg:col-span-4">
            <div className="sticky top-24 rounded-xl border border-border-subtle bg-surface p-5">
              <div className="mb-4 flex items-center justify-between border-b-2 border-accent pb-3">
                <h2 className="text-base font-extrabold uppercase tracking-tight text-heading">
                  Berita Terkait
                </h2>
                <span className="text-xs font-bold text-accent">Baca Juga</span>
              </div>
              <div className="divide-y divide-border-subtle">
                {related.map((item) => (
                  <button
                    className="group flex w-full cursor-pointer gap-3 py-3 text-left first:pt-0 last:pb-0"
                    key={item.id}
                    onClick={() => openArticle(item)}
                    type="button"
                  >
                    <Image
                      alt={item.title}
                      className="h-16 w-20 shrink-0 rounded-lg object-cover"
                      height={64}
                      src={item.image}
                      width={80}
                    />
                    <span className="min-w-0">
                      <span className="text-[10px] font-extrabold uppercase text-accent">
                        {item.region}
                      </span>
                      <span className="mt-0.5 line-clamp-2 block text-xs font-extrabold leading-snug text-heading group-hover:text-brand">
                        {item.title}
                      </span>
                    </span>
                  </button>
                ))}
              </div>
            </div>
          </aside>
        </div>
      </section>
    );
  }

  function renderFooter() {
    return (
      <footer className="mt-16 border-t-[3px] border-brand bg-surface">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
          <div className="grid grid-cols-1 gap-10 md:grid-cols-12">
            <div className="space-y-4 md:col-span-4">
              <div className="flex items-center gap-2.5">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-lg font-black tracking-tighter text-white">
                  LG
                </span>
                <span className="flex flex-col leading-none">
                  <span className="text-xl font-black tracking-tight text-heading">
                    LAMPUNG <span className="text-brand">GEH</span>
                  </span>
                  <span className="mt-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-muted">
                    NEWS PORTAL
                  </span>
                </span>
              </div>
              <p className="text-sm leading-relaxed text-body">
                <strong>
                  Berita Terkini dan Terpercaya seputar Lampung!
                </strong>{" "}
                Menyajikan liputan jernih, cepat, dan mendalam dari 15
                Kabupaten/Kota di Provinsi Lampung tanpa gangguan iklan.
              </p>
              <div className="flex items-center gap-2.5 pt-1">
                {[
                  ["Instagram", "https://instagram.com/lampunggehnews"],
                  ["Facebook", "https://facebook.com/lampunggeh"],
                  ["X (@lampunggehh)", "https://x.com/lampunggehh"],
                ].map(([label, href]) => (
                  <a
                    className="rounded-lg border border-border-subtle bg-canvas px-3 py-2 text-xs font-bold text-heading transition hover:bg-brand hover:text-white"
                    href={href}
                    key={label}
                    rel="noopener noreferrer"
                    target="_blank"
                  >
                    {label}
                  </a>
                ))}
              </div>
            </div>
            <div className="md:col-span-5">
              <h2 className="mb-4 text-sm font-extrabold uppercase tracking-wider text-heading">
                Daerah — 15 Kabupaten/Kota Lampung
              </h2>
              <div className="flex flex-wrap gap-2">
                {regions.map((region) => (
                  <button
                    className="cursor-pointer rounded-lg border border-border-subtle bg-canvas px-2.5 py-1.5 text-xs font-bold text-heading transition hover:bg-accent hover:text-white"
                    key={region}
                    onClick={() => filterByRegion(region)}
                    type="button"
                  >
                    {region}
                  </button>
                ))}
              </div>
            </div>
            <div className="space-y-6 md:col-span-3">
              <div>
                <h2 className="mb-3 text-sm font-extrabold uppercase tracking-wider text-heading">
                  Tentang Redaksi
                </h2>
                <ul className="space-y-2 text-sm font-semibold text-body">
                  <li>
                    <button
                      className="transition hover:text-brand"
                      onClick={() => setStaticModal("about")}
                      type="button"
                    >
                      About Lampung Geh News
                    </button>
                  </li>
                  <li>
                    <button
                      className="transition hover:text-brand"
                      onClick={() => setStaticModal("pedoman")}
                      type="button"
                    >
                      Pedoman Media Siber
                    </button>
                  </li>
                </ul>
              </div>
              <div>
                <h2 className="mb-3 text-sm font-extrabold uppercase tracking-wider text-heading">
                  Ekosistem Lampung Geh
                </h2>
                <ul className="space-y-2 text-sm font-semibold text-body">
                  <li>
                    <a href="https://lampuung.com" rel="noreferrer" target="_blank">
                      Web Lampung Geh (lampuung.com)
                    </a>
                  </li>
                  <li>
                    <a
                      href="https://lowonganlampung.co.id"
                      rel="noreferrer"
                      target="_blank"
                    >
                      Lowongan Lampung
                    </a>
                  </li>
                  <li>
                    <a
                      href="https://shopee.co.id/lampunggehcatalog"
                      rel="noreferrer"
                      target="_blank"
                    >
                      Lampung Geh Store
                    </a>
                  </li>
                  <li>
                    <a href="https://lampuung.com/join" rel="noreferrer" target="_blank">
                      Join Kolaborasi
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div className="border-t border-border-subtle bg-surface-elevated">
          <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 text-[13px] text-body sm:flex-row sm:px-6">
            <p className="font-semibold">
              © 2021–2026 PT Lampung Geh Helau. Hak Cipta Dilindungi
              Undang-Undang.
            </p>
            <p className="text-xs text-muted">
              Ad-Free Local Journalism • Bandar Lampung, Indonesia
            </p>
          </div>
        </div>
      </footer>
    );
  }

  const government = articles.filter(
    (article) =>
      article.subCategory === "Pemerintahan" || article.category === "News",
  );
  const sports = articles.filter(
    (article) =>
      article.subCategory === "Olahraga" || article.category === "Lifestyle",
  );
  const recommended = articles.filter((article) => article.isRecommended).slice(0, 3);
  const featured = articles
    .filter((article) => article.isFeatured || article.category === "Cerita Opini")
    .slice(0, 2);
  const videos = articles.filter((article) => article.hasVideo);

  return (
    <div id="top">
      {renderHeader()}

      {filteredArticles ? (
        <div className="border-b border-border-subtle bg-surface">
          <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div className="flex items-center gap-2 text-sm">
              <span className="font-medium text-muted">Menampilkan arsip:</span>
              <span className="rounded bg-accent px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-white">
                {filterLabel}
              </span>
              <span className="text-xs font-semibold text-muted">
                ({filteredArticles.length} artikel)
              </span>
            </div>
            <button
              className="flex items-center gap-1 text-xs font-extrabold text-brand hover:text-brand-dark"
              onClick={resetHome}
              type="button"
            >
              × Kembali ke Beranda Utama
            </button>
          </div>
        </div>
      ) : null}

      {activeArticle ? (
        renderArticleDetail(activeArticle)
      ) : filteredArticles ? (
        <main className="mx-auto min-h-[50vh] max-w-7xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
          <h1 className="border-b-2 border-heading pb-3 text-2xl font-extrabold tracking-tight text-heading sm:text-3xl">
            {filterLabel}
          </h1>
          {filteredArticles.length > 0 ? (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {filteredArticles.map((article) => (
                <StandardCard
                  article={article}
                  key={article.id}
                  onBookmark={toggleBookmark}
                  onOpen={openArticle}
                  saved={bookmarks.includes(article.id)}
                />
              ))}
            </div>
          ) : (
            <div className="rounded-xl border border-border-subtle bg-surface py-12 text-center">
              <p className="text-base font-extrabold text-heading">
                Tidak ada artikel yang cocok dengan filter ini.
              </p>
              <button
                className="mt-3 rounded-lg bg-brand px-4 py-2 text-xs font-bold text-white"
                onClick={resetHome}
                type="button"
              >
                Tampilkan Semua Berita
              </button>
            </div>
          )}
        </main>
      ) : (
        <main className="mx-auto max-w-7xl space-y-12 px-4 py-6 sm:px-6 sm:py-8">
          <div className="space-y-12">
            <section
              aria-label="Headline & Top Stories"
              className="grid grid-cols-1 gap-6 lg:grid-cols-12"
            >
              <div className="lg:col-span-8">
                <div className="group relative aspect-[16/10] overflow-hidden rounded-xl border border-border-subtle bg-heading sm:aspect-[16/9]">
                  <Image
                    alt={hero.title}
                    className="object-cover transition-transform duration-500 group-hover:scale-[1.02]"
                    fill
                    priority
                    sizes="(max-width: 1024px) 100vw, 66vw"
                    src={hero.image}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-[#1a1a1a] via-[#1a1a1a]/55 to-transparent" />
                  <div className="absolute left-4 right-4 top-4 flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                      {hero.isLive ? (
                        <span className="flex items-center gap-1.5 rounded bg-brand px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-white shadow">
                          <span className="live-dot h-2 w-2 rounded-full bg-white" />
                          LIVE REPORT
                        </span>
                      ) : null}
                      <span className="rounded bg-brand px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-white shadow">
                        {hero.category}
                      </span>
                      <span className="rounded bg-canvas/90 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-accent">
                        {hero.region}
                      </span>
                    </div>
                    <button
                      aria-label="Simpan Berita"
                      className="rounded-lg bg-black/45 p-2 text-white backdrop-blur-sm transition hover:bg-brand"
                      onClick={() => toggleBookmark(hero)}
                      title="Simpan Berita"
                      type="button"
                    >
                      <BookmarkIcon filled={bookmarks.includes(hero.id)} />
                    </button>
                  </div>
                  <div className="absolute bottom-0 left-0 right-0 p-5 sm:p-7">
                    <h1 className="text-xl font-extrabold leading-tight text-white sm:text-2xl sm:leading-[1.22] lg:text-[31px]">
                      <button
                        className="cursor-pointer text-left decoration-brand decoration-2 underline-offset-4 hover:underline"
                        onClick={() => openArticle(hero)}
                        type="button"
                      >
                        {hero.title}
                      </button>
                    </h1>
                    <p className="mt-2.5 hidden max-w-2xl line-clamp-2 text-sm text-white/80 sm:block">
                      {hero.excerpt}
                    </p>
                    <div className="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-white/15 pt-3">
                      <div className="flex items-center gap-3 text-xs font-medium text-white/80">
                        <span className="font-bold text-white">{hero.author}</span>
                        <span>•</span>
                        <span>{hero.publishedAt}</span>
                        <span>•</span>
                        <span>{hero.readTime} menit baca</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <div className="mr-2 flex items-center gap-1.5">
                          {headlines.map((article, index) => (
                            <button
                              aria-label={`Slide ${index + 1}`}
                              className={`h-2 rounded-full transition-all ${
                                index === heroIndex
                                  ? "w-6 bg-brand"
                                  : "w-2 bg-white/40 hover:bg-white/75"
                              }`}
                              key={article.id}
                              onClick={() => setHeroIndex(index)}
                              type="button"
                            />
                          ))}
                        </div>
                        <button
                          aria-label="Slide Sebelumnya"
                          className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 text-white transition hover:bg-brand"
                          onClick={() =>
                            setHeroIndex(
                              (index) => (index - 1 + headlines.length) % headlines.length,
                            )
                          }
                          type="button"
                        >
                          ‹
                        </button>
                        <button
                          aria-label="Slide Berikutnya"
                          className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 text-white transition hover:bg-brand"
                          onClick={() =>
                            setHeroIndex((index) => (index + 1) % headlines.length)
                          }
                          type="button"
                        >
                          ›
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <aside className="flex flex-col justify-between rounded-xl border border-border-subtle bg-surface p-5 lg:col-span-4">
                <div>
                  <div className="mb-4 flex items-center justify-between border-b-2 border-brand pb-3">
                    <div className="flex items-center gap-2">
                      <span className="h-2.5 w-2.5 rounded-full bg-brand" />
                      <h2 className="text-lg font-extrabold uppercase tracking-tight text-heading">
                        Terkini
                      </h2>
                    </div>
                    <span className="text-xs font-bold text-muted">
                      Update Real-Time
                    </span>
                  </div>
                  <div className="divide-y divide-border-subtle">
                    {articles.slice(1, 5).map((article, index) => (
                      <article
                        className="group flex cursor-pointer items-start gap-3 py-3 first:pt-0 last:pb-0"
                        key={article.id}
                        onClick={() => openArticle(article)}
                      >
                        <span className="mt-0.5 text-lg font-black leading-none text-border-subtle transition group-hover:text-brand">
                          0{index + 1}
                        </span>
                        <div className="min-w-0 flex-1">
                          <span className="text-[10px] font-extrabold uppercase tracking-wider text-accent">
                            {article.region}
                          </span>
                          <h3 className="mt-0.5 line-clamp-2 text-sm font-extrabold leading-snug text-heading transition group-hover:text-brand">
                            {article.title}
                          </h3>
                          <span className="mt-1 block text-[11px] text-muted">
                            {article.publishedAt} • {article.readTime} mnt baca
                          </span>
                        </div>
                      </article>
                    ))}
                  </div>
                </div>
                <button
                  className="mt-4 w-full rounded-lg border border-border-subtle bg-canvas py-2.5 text-xs font-extrabold uppercase tracking-wider text-heading transition hover:bg-surface-elevated"
                  onClick={() =>
                    document
                      .getElementById("section-latest-posts")
                      ?.scrollIntoView({ behavior: "smooth" })
                  }
                  type="button"
                >
                  Indeks Berita Terkini →
                </button>
              </aside>
            </section>

            <section aria-labelledby="heading-berita-terkini">
              <div className="mb-6 flex items-center justify-between border-b border-border-subtle pb-3">
                <div className="flex items-center gap-2.5">
                  <span className="h-6 w-1.5 rounded-full bg-brand" />
                  <h2
                    className="text-[22px] font-extrabold tracking-tight text-heading"
                    id="heading-berita-terkini"
                  >
                    Berita Terkini
                  </h2>
                </div>
                <span className="text-xs font-bold uppercase tracking-wider text-accent">
                  Lampung &amp; Nasional
                </span>
              </div>
              <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                {articles.slice(0, 4).map((article) => (
                  <StandardCard
                    article={article}
                    key={article.id}
                    onBookmark={toggleBookmark}
                    onOpen={openArticle}
                    saved={bookmarks.includes(article.id)}
                  />
                ))}
              </div>
            </section>

            <section className="grid grid-cols-1 gap-8 lg:grid-cols-12">
              <div className="lg:col-span-7">
                <div className="mb-5 flex items-center justify-between border-b border-border-subtle pb-3">
                  <div className="flex items-center gap-2.5">
                    <span className="h-6 w-1.5 rounded-full bg-accent" />
                    <h2 className="text-[22px] font-extrabold tracking-tight text-heading">
                      Pemerintahan
                    </h2>
                  </div>
                  <button
                    className="text-xs font-extrabold text-accent hover:underline"
                    onClick={() => filterBySubCategory("Pemerintahan")}
                    type="button"
                  >
                    Semua Pemerintahan →
                  </button>
                </div>
                {government[0] ? (
                  <div className="space-y-4">
                    <article
                      className="group grid cursor-pointer grid-cols-1 overflow-hidden rounded-xl border border-border-subtle bg-surface sm:grid-cols-12"
                      onClick={() => openArticle(government[0])}
                    >
                      <div className="relative aspect-[16/10] overflow-hidden sm:col-span-5 sm:aspect-auto">
                        <Image
                          alt={government[0].title}
                          className="object-cover transition-transform duration-300 group-hover:scale-105"
                          fill
                          sizes="(max-width: 640px) 100vw, 300px"
                          src={government[0].image}
                        />
                      </div>
                      <div className="flex flex-col justify-between p-5 sm:col-span-7">
                        <div>
                          <span className="text-[11px] font-extrabold uppercase tracking-wider text-accent">
                            {government[0].region}
                          </span>
                          <h3 className="mt-1 text-lg font-extrabold leading-snug text-heading transition group-hover:text-brand">
                            {government[0].title}
                          </h3>
                          <p className="mt-2 line-clamp-2 text-xs text-body">
                            {government[0].excerpt}
                          </p>
                        </div>
                        <div className="mt-3 border-t border-border-subtle pt-2 text-xs text-muted">
                          {government[0].author} • {government[0].publishedAt}
                        </div>
                      </div>
                    </article>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      {government.slice(1, 3).map((article) => (
                        <article
                          className="group cursor-pointer rounded-xl border border-border-subtle bg-canvas p-4 transition hover:border-accent/50"
                          key={article.id}
                          onClick={() => openArticle(article)}
                        >
                          <span className="text-[10px] font-extrabold uppercase tracking-wider text-accent">
                            {article.region}
                          </span>
                          <h3 className="mt-1 line-clamp-2 text-sm font-extrabold leading-snug text-heading transition group-hover:text-brand">
                            {article.title}
                          </h3>
                          <span className="mt-2 block text-[11px] text-muted">
                            {article.publishedAt} • {article.readTime} mnt baca
                          </span>
                        </article>
                      ))}
                    </div>
                  </div>
                ) : null}
              </div>

              <div className="lg:col-span-5">
                <div className="mb-5 flex items-center justify-between border-b border-border-subtle pb-3">
                  <div className="flex items-center gap-2.5">
                    <span className="h-6 w-1.5 rounded-full bg-brand" />
                    <h2 className="text-[22px] font-extrabold tracking-tight text-heading">
                      Olahraga
                    </h2>
                  </div>
                  <button
                    className="text-xs font-extrabold text-brand hover:underline"
                    onClick={() => filterBySubCategory("Olahraga")}
                    type="button"
                  >
                    Semua Olahraga →
                  </button>
                </div>
                <div className="space-y-4 rounded-xl border border-border-subtle bg-surface p-5">
                  {sports.map((article) => (
                    <StoryListCard
                      article={article}
                      key={article.id}
                      onOpen={openArticle}
                    />
                  ))}
                </div>
              </div>
            </section>

            <section className="rounded-2xl border border-border-subtle bg-surface p-6 sm:p-8">
              <div className="mb-6 flex flex-wrap items-center justify-between gap-2 border-b border-border-subtle pb-4">
                <div className="flex items-center gap-2.5">
                  <span className="rounded bg-accent px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-white">
                    PILIHAN REDAKSI
                  </span>
                  <h2 className="text-[22px] font-extrabold tracking-tight text-heading">
                    Recommended
                  </h2>
                </div>
                <p className="text-xs font-medium text-muted">
                  Liputan mendalam &amp; analisis pilihan editor Lampung Geh
                </p>
              </div>
              <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {recommended.map((article) => (
                  <StandardCard
                    article={article}
                    key={article.id}
                    onBookmark={toggleBookmark}
                    onOpen={openArticle}
                    saved={bookmarks.includes(article.id)}
                  />
                ))}
              </div>
            </section>

            <section>
              <SectionTitle>Jelajahi Rubrik</SectionTitle>
              <div className="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-6">
                {categories.map((category, index) => (
                  <button
                    className={`group rounded-xl border border-border-subtle bg-surface p-4 text-left transition ${
                      index < 3 ? "hover:bg-heading" : "hover:bg-accent"
                    }`}
                    key={category.name}
                    onClick={() => filterByCategory(category.name)}
                    type="button"
                  >
                    <span
                      className={`mb-3 inline-block h-2.5 w-2.5 rounded-full ${
                        index < 3 ? "bg-brand" : "bg-accent group-hover:bg-white"
                      }`}
                    />
                    <h3
                      className={`text-base font-extrabold text-heading group-hover:text-white ${
                        category.name === "Cerita Opini" ? "font-serif" : ""
                      }`}
                    >
                      {category.name}
                    </h3>
                    <p className="mt-1 text-xs text-muted group-hover:text-white/75">
                      {category.description}
                    </p>
                  </button>
                ))}
              </div>
            </section>

            <section>
              <div className="mb-6 flex items-center justify-between border-b border-border-subtle pb-3">
                <div className="flex items-center gap-2.5">
                  <span className="h-6 w-1.5 rounded-full bg-accent" />
                  <h2 className="text-[22px] font-extrabold tracking-tight text-heading">
                    Featured News &amp; Cerita Opini
                  </h2>
                </div>
                <button
                  className="text-xs font-extrabold text-accent hover:underline"
                  onClick={() => filterByCategory("Cerita Opini")}
                  type="button"
                >
                  Buka Rubrik Opini →
                </button>
              </div>
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {featured.map((article) => (
                  <article
                    className="group flex cursor-pointer flex-col overflow-hidden rounded-xl border border-border-subtle bg-surface sm:flex-row"
                    key={article.id}
                    onClick={() => openArticle(article)}
                  >
                    <div className="relative aspect-[16/10] shrink-0 overflow-hidden sm:aspect-auto sm:w-2/5">
                      <Image
                        alt={article.title}
                        className="object-cover transition-transform duration-300 group-hover:scale-105"
                        fill
                        sizes="(max-width: 640px) 100vw, 40vw"
                        src={article.image}
                      />
                    </div>
                    <div className="flex flex-col justify-between p-5">
                      <div>
                        <span className="rounded bg-accent/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-accent">
                          {article.category}
                        </span>
                        <h3 className="mt-2 font-serif text-lg font-bold leading-snug text-heading transition group-hover:text-brand">
                          {article.title}
                        </h3>
                        <p className="mt-2 line-clamp-2 text-xs text-body">
                          {article.excerpt}
                        </p>
                      </div>
                      <div className="mt-4 text-xs text-muted">
                        Oleh <strong className="text-heading">{article.author}</strong>{" "}
                        • {article.readTime} mnt baca
                      </div>
                    </div>
                  </article>
                ))}
              </div>
            </section>

            <section className="rounded-2xl bg-heading p-6 text-white sm:p-8">
              <div className="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-white/15 pb-4">
                <div className="flex items-center gap-2.5">
                  <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand">
                    <svg
                      className="h-4 w-4 fill-current text-white"
                      viewBox="0 0 24 24"
                    >
                      <path d="M8 5v14l11-7z" />
                    </svg>
                  </span>
                  <div>
                    <h2 className="text-[22px] font-extrabold leading-none tracking-tight text-white">
                      Featured Video
                    </h2>
                    <p className="mt-1 text-xs text-white/65">
                      Liputan lapangan visual dari YouTube, Reels &amp; TikTok
                      Lampung Geh
                    </p>
                  </div>
                </div>
                <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white/90">
                  Multimedia
                </span>
              </div>
              <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {videos.map((article) => (
                  <article
                    className="group cursor-pointer"
                    key={article.id}
                    onClick={() => openArticle(article)}
                  >
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-white/10 bg-black">
                      <Image
                        alt={article.title}
                        className="object-cover opacity-85 transition-transform duration-300 group-hover:scale-105"
                        fill
                        sizes="(max-width: 1024px) 50vw, 33vw"
                        src={article.image}
                      />
                      <div className="absolute inset-0 flex items-center justify-center bg-black/25">
                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/95 text-white shadow-lg transition group-hover:scale-110">
                          ▶
                        </span>
                      </div>
                      <span className="absolute bottom-2.5 left-2.5 rounded bg-black/80 px-2.5 py-1 text-[11px] font-bold text-white">
                        {article.videoPlatform}
                      </span>
                    </div>
                    <h3 className="mt-3 text-base font-extrabold leading-snug text-white transition group-hover:text-brand">
                      {article.title}
                    </h3>
                    <p className="mt-1 text-xs text-white/60">
                      {article.region} • {article.publishedAt}
                    </p>
                  </article>
                ))}
              </div>
            </section>

            <section
              className="grid grid-cols-1 gap-8 pt-2 lg:grid-cols-12"
              id="section-latest-posts"
            >
              <div className="lg:col-span-8">
                <div className="mb-6 flex items-center justify-between border-b-2 border-heading pb-3">
                  <h2 className="text-[22px] font-extrabold tracking-tight text-heading">
                    Latest Posts
                  </h2>
                  <span className="text-xs font-bold text-muted">
                    Urutan Kronologis Terbaru
                  </span>
                </div>
                <div className="space-y-5">
                  {articles.slice(0, latestLimit).map((article) => (
                    <LatestPost
                      article={article}
                      key={article.id}
                      onBookmark={toggleBookmark}
                      onOpen={openArticle}
                      saved={bookmarks.includes(article.id)}
                    />
                  ))}
                </div>
                <div className="mt-8 flex items-center justify-center">
                  <button
                    className="rounded-xl border border-border-subtle bg-surface px-6 py-3 text-sm font-extrabold text-heading transition hover:bg-surface-elevated"
                    disabled={latestLimit >= articles.length}
                    onClick={() => setLatestLimit(articles.length)}
                    type="button"
                  >
                    {latestLimit >= articles.length
                      ? "Seluruh Berita Terbaru Telah Ditampilkan"
                      : "Muat Berita Sebelumnya"}
                  </button>
                </div>
              </div>
              <aside className="space-y-6 lg:col-span-4">
                <div className="rounded-xl border border-border-subtle bg-surface p-5">
                  <div className="mb-4 flex items-center justify-between border-b border-border-subtle pb-3">
                    <h3 className="text-base font-extrabold text-heading">
                      Berita Per Daerah
                    </h3>
                    <span className="text-[11px] font-extrabold uppercase text-accent">
                      15 Kab/Kota
                    </span>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {regions.map((region) => (
                      <button
                        className="cursor-pointer rounded-lg border border-border-subtle bg-canvas px-2.5 py-1.5 text-xs font-bold text-heading transition hover:bg-accent hover:text-white"
                        key={region}
                        onClick={() => filterByRegion(region)}
                        type="button"
                      >
                        {region}
                      </button>
                    ))}
                  </div>
                </div>
                <div className="rounded-xl border border-border-subtle bg-canvas p-5">
                  <span className="mb-2.5 inline-block rounded bg-brand/10 px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider text-brand">
                    Ad-Free Experience
                  </span>
                  <h3 className="text-base font-extrabold leading-snug text-heading">
                    Membaca Berita Lampung Tanpa Gangguan Iklan
                  </h3>
                  <p className="mt-2 text-xs leading-relaxed text-body">
                    Lampung Geh News menghadirkan jurnalisme lokal yang jernih,
                    cepat, dan bebas dari slot banner komersial maupun pop-up
                    yang mengganggu kenyamanan membaca Anda.
                  </p>
                  <div className="mt-4 flex items-center justify-between border-t border-border-subtle pt-3 text-xs font-bold">
                    <button
                      className="text-accent hover:underline"
                      onClick={() => setStaticModal("about")}
                      type="button"
                    >
                      Tentang Redaksi
                    </button>
                    <button
                      className="text-muted hover:text-heading"
                      onClick={() => setStaticModal("pedoman")}
                      type="button"
                    >
                      Pedoman Siber
                    </button>
                  </div>
                </div>
              </aside>
            </section>
          </div>
        </main>
      )}

      {renderFooter()}

      {bookmarkDrawer || mobileMenu ? (
        <button
          aria-label="Tutup menu"
          className="fixed inset-0 z-50 bg-black/50 backdrop-blur-[2px]"
          onClick={closeDrawers}
          type="button"
        />
      ) : null}
      <aside
        className={`fixed bottom-0 right-0 top-0 z-[51] flex w-full max-w-md flex-col border-l border-border-subtle bg-canvas shadow-2xl transition-transform duration-300 ${
          bookmarkDrawer ? "translate-x-0" : "translate-x-full"
        }`}
        aria-label="Berita Tersimpan"
        aria-hidden={!bookmarkDrawer}
      >
        <div className="flex items-center justify-between border-b border-border-subtle bg-surface px-5 py-4">
          <div className="flex items-center gap-2">
            <span className="text-brand">
              <BookmarkIcon filled />
            </span>
            <div>
              <h2 className="text-base font-extrabold text-heading">
                Berita Tersimpan
              </h2>
              <p className="text-xs text-muted">{savedArticles.length} artikel</p>
            </div>
          </div>
          <button
            aria-label="Tutup berita tersimpan"
            className="rounded-lg p-2 text-heading hover:bg-surface-elevated"
            onClick={() => setBookmarkDrawer(false)}
            type="button"
          >
            ×
          </button>
        </div>
        <div className="flex-1 space-y-3 overflow-y-auto p-4">
          {savedArticles.length ? (
            savedArticles.map((article) => (
              <div
                className="group flex gap-3 rounded-xl border border-border-subtle bg-surface p-3"
                key={article.id}
              >
                <button
                  className="relative h-16 w-20 shrink-0 cursor-pointer overflow-hidden rounded-lg"
                  onClick={() => {
                    setBookmarkDrawer(false);
                    openArticle(article);
                  }}
                  type="button"
                >
                  <Image
                    alt={article.title}
                    className="object-cover"
                    fill
                    sizes="80px"
                    src={article.image}
                  />
                </button>
                <div className="min-w-0 flex-1">
                  <span className="text-[10px] font-extrabold uppercase tracking-wider text-accent">
                    {article.category}
                  </span>
                  <button
                    className="mt-0.5 line-clamp-2 block cursor-pointer text-left text-xs font-extrabold text-heading group-hover:text-brand"
                    onClick={() => {
                      setBookmarkDrawer(false);
                      openArticle(article);
                    }}
                    type="button"
                  >
                    {article.title}
                  </button>
                  <div className="mt-1.5 flex items-center justify-between">
                    <span className="text-[11px] text-muted">
                      {article.readTime} mnt baca
                    </span>
                    <button
                      className="text-[11px] font-bold text-brand hover:underline"
                      onClick={() => toggleBookmark(article)}
                      type="button"
                    >
                      Hapus
                    </button>
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="px-4 py-14 text-center">
              <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface text-muted">
                <BookmarkIcon />
              </div>
              <p className="text-sm font-extrabold text-heading">
                Belum Ada Berita Tersimpan
              </p>
              <p className="mt-1 text-xs text-muted">
                Klik ikon simpan pada kartu berita mana pun untuk membacanya
                nanti.
              </p>
            </div>
          )}
        </div>
        {savedArticles.length ? (
          <div className="border-t border-border-subtle p-4">
            <button
              className="w-full rounded-lg border border-border-subtle bg-surface py-2.5 text-sm font-bold text-heading hover:bg-surface-elevated"
              onClick={() => {
                saveBookmarks([]);
                showMessage("Seluruh berita tersimpan telah dihapus");
              }}
              type="button"
            >
              Hapus Semua
            </button>
          </div>
        ) : null}
      </aside>

      <div
        className={`fixed bottom-0 left-0 top-0 z-[51] flex w-[min(85vw,340px)] flex-col border-r border-border-subtle bg-canvas shadow-2xl transition-transform duration-300 ${
          mobileMenu ? "translate-x-0" : "-translate-x-full"
        }`}
        aria-hidden={!mobileMenu}
      >
        <div className="flex items-center justify-between border-b border-border-subtle p-4">
          <span className="font-black text-heading">
            LAMPUNG <span className="text-brand">GEH</span>
          </span>
          <button
            aria-label="Tutup Menu"
            className="rounded-lg p-2 text-heading hover:bg-surface"
            onClick={() => setMobileMenu(false)}
            type="button"
          >
            ×
          </button>
        </div>
        <nav className="space-y-1 p-4">
          <button
            className="w-full rounded-lg px-3 py-2.5 text-left text-sm font-bold text-brand hover:bg-surface"
            onClick={resetHome}
            type="button"
          >
            Home
          </button>
          {categories.map((category) => (
            <button
              className="w-full rounded-lg px-3 py-2.5 text-left text-sm font-bold text-heading hover:bg-surface"
              key={category.name}
              onClick={() => filterByCategory(category.name)}
              type="button"
            >
              {category.name}
            </button>
          ))}
        </nav>
        <div className="mt-auto border-t border-border-subtle p-4">
          <p className="mb-3 text-xs font-extrabold uppercase tracking-wider text-muted">
            Berita Per Daerah
          </p>
          <div className="flex flex-wrap gap-2">
            {regions.map((region) => (
              <button
                className="rounded bg-surface px-2.5 py-1 text-[11px] font-bold text-heading hover:bg-accent hover:text-white"
                key={region}
                onClick={() => filterByRegion(region)}
                type="button"
              >
                {region}
              </button>
            ))}
          </div>
        </div>
      </div>

      {staticModal ? (
        <div
          className="fixed inset-0 z-[60] flex items-center justify-center bg-black/55 p-4"
          onClick={() => setStaticModal(null)}
          role="presentation"
        >
          <section
            aria-labelledby="static-modal-title"
            aria-modal="true"
            className="w-full max-w-xl rounded-2xl bg-canvas p-6 shadow-2xl sm:p-8"
            onClick={(event) => event.stopPropagation()}
            role="dialog"
          >
            <div className="mb-4 flex items-center justify-between">
              <h2
                className="text-xl font-extrabold text-heading"
                id="static-modal-title"
              >
                {staticModal === "about"
                  ? "Tentang Lampung Geh News"
                  : "Pedoman Media Siber"}
              </h2>
              <button
                aria-label="Tutup"
                className="rounded-lg p-2 text-heading hover:bg-surface"
                onClick={() => setStaticModal(null)}
                type="button"
              >
                ×
              </button>
            </div>
            <div className="space-y-4 text-sm leading-relaxed text-body">
              {staticModal === "about" ? (
                <>
                  <p>
                    <strong>Lampung Geh News</strong> (
                    <span className="font-bold text-accent">
                      lampunggehnews.com
                    </span>
                    ) adalah portal berita digital di bawah naungan{" "}
                    <strong>PT Lampung Geh Helau</strong> yang menyajikan
                    informasi akurat, berimbang, dan terpercaya seputar Provinsi
                    Lampung dan isu strategis nasional.
                  </p>
                  <p>
                    Kami mengusung pengalaman membaca bebas iklan (Ad-Free
                    Reading Experience) agar setiap pembaca mendapatkan
                    kenyamanan penuh saat mengakses informasi publik dari
                    seluruh 15 Kabupaten/Kota di Lampung.
                  </p>
                </>
              ) : (
                <>
                  <p>
                    Kemerdekaan berpendapat, kemerdekaan berekspresi, dan
                    kemerdekaan pers adalah hak asasi manusia yang dilindungi
                    Pancasila, Undang-Undang Dasar 1945, dan Deklarasi Universal
                    Hak Asasi Manusia PBB.
                  </p>
                  <p>
                    Redaksi <strong>Lampung Geh News</strong> tunduk pada Kode
                    Etik Jurnalistik dan Pedoman Pemberitaan Media Siber yang
                    ditetapkan oleh Dewan Pers Republik Indonesia.
                  </p>
                </>
              )}
            </div>
          </section>
        </div>
      ) : null}

      <div
        aria-live="polite"
        className={`fixed bottom-5 left-1/2 z-[70] flex -translate-x-1/2 items-center gap-2 rounded-lg bg-heading px-4 py-3 text-sm font-semibold text-white shadow-xl transition-all ${
          toast ? "translate-y-0 opacity-100" : "translate-y-20 opacity-0"
        }`}
      >
        <span className="h-2 w-2 rounded-full bg-green-500" />
        {toast}
      </div>
    </div>
  );
}
