import { useEffect, useRef, useState } from "react";
import "./StatsSection.css";
import { FaUserGraduate, FaBook, FaUsers, FaTrophy } from "react-icons/fa";
import type { Stat } from "../../services/homepage";
import { homepageService } from "../../services/homepage";
import type { IconType } from "react-icons";
import { resolveHomepageImage } from "../../utils/assetUrl";
import ResponsiveImage from "../Media/ResponsiveImage";

const iconMap: Record<string, IconType> = {
  FaUserGraduate: FaUserGraduate,
  FaBook: FaBook,
  FaUsers: FaUsers,
  FaTrophy: FaTrophy,
};

interface StatWithImage extends Stat {
  imagem?: string;
}

function StatCard({
  stat,
  shouldCount,
}: {
  stat: StatWithImage;
  shouldCount: boolean;
}) {
  const ref = useRef<HTMLSpanElement>(null);
  const [imageLoadError, setImageLoadError] = useState(false);

  useEffect(() => {
    if (!shouldCount) return;
    const el = ref.current;
    if (!el) return;

    let start = 0;
    const target = stat.valor;
    const duration = 5000;
    const step = Math.max(1, Math.ceil(target / (duration / 16)));

    const id = setInterval(() => {
      start += step;
      if (start >= target) {
        start = target;
        clearInterval(id);
      }
      el.textContent = String(start);
    }, 16);

    return () => clearInterval(id);
  }, [stat.valor, shouldCount]);

  const IconComponent = iconMap[stat.icone] || FaUserGraduate;
  const hasImage = stat.imagem && stat.imagem.trim() !== "";
  const imageUrl = hasImage ? resolveHomepageImage(stat.imagem) : null;
  const showImage = Boolean(hasImage && imageUrl && !imageLoadError);

  return (
    <div className="stat" key={stat.id_stat}>
      {showImage ? (
        <ResponsiveImage
          src={imageUrl ?? ""}
          alt={stat.label}
          className="stat-image"
          sizes="(max-width: 480px) 32px, (max-width: 768px) 40px, (max-width: 1024px) 48px, 56px"
          width={56}
          height={56}
          onError={() => setImageLoadError(true)}
        />
      ) : null}
      <IconComponent
        className="stat-icon"
        style={showImage ? { display: "none" } : {}}
      />
      <span ref={ref} className="num">
        0
      </span>
      <span className="label">{stat.label}</span>
    </div>
  );
}

export default function StatsSection() {
  const [hasScrolled, setHasScrolled] = useState(false);
  const [stats, setStats] = useState<Stat[]>([]);
  const [loading, setLoading] = useState(true);
  const sectionRef = useRef<HTMLDivElement>(null);

  // Buscar stats da API
  useEffect(() => {
    const fetchStats = async () => {
      setLoading(true);
      const data = await homepageService.getStats();
      if (data && data.length > 0) {
        setStats(data);
      }
      setLoading(false);
    };
    fetchStats();
  }, []);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && !hasScrolled) {
          setHasScrolled(true);
        }
      },
      { threshold: 0.3 }
    );

    const currentRef = sectionRef.current;
    if (currentRef) {
      observer.observe(currentRef);
    }

    return () => {
      if (currentRef) {
        observer.unobserve(currentRef);
      }
    };
  }, [hasScrolled]);

  if (loading || !Array.isArray(stats) || stats.length === 0) {
    return (
      <section
        className="stats-section"
        ref={sectionRef}
        style={{ minHeight: "200px" }}
      />
    );
  }

  return (
    <section className="stats-section" ref={sectionRef}>
      <div className="container stats-grid">
        {stats.map((stat) => (
          <StatCard key={stat.id_stat} stat={stat} shouldCount={hasScrolled} />
        ))}
      </div>
    </section>
  );
}
