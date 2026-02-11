import { useState, useRef, useEffect } from "react";
import "./FAQSection.css";
import type { FAQ } from "../../services/homepage";
import { homepageService } from "../../services/homepage";

export default function FAQSection() {
  const [faqs, setFaqs] = useState<FAQ[]>([]);
  const [loading, setLoading] = useState(true);

  
  useEffect(() => {
    const fetchFAQ = async () => {
      setLoading(true);
      const data = await homepageService.getFAQ();
      if (data && data.length > 0) {
        setFaqs(data);
      }
      setLoading(false);
    };
    fetchFAQ();
  }, []);

  if (loading || !Array.isArray(faqs) || faqs.length === 0) {
    return (
      <section
        className="faq-section"
        id="duvidas"
        style={{ minHeight: "400px" }}
      />
    );
  }

  return (
    <section className="faq-section" id="duvidas">
      <h2 className="section-title">Dúvidas frequentes</h2>
      <div className="container">
        {faqs.map((f) => (
          <FAQItem key={f.id_faq} q={f.pergunta} a={f.resposta} />
        ))}
      </div>
    </section>
  );
}

function FAQItem({ q, a }: { q: string; a: string }) {
  const [open, setOpen] = useState(false);
  const contentRef = useRef<HTMLDivElement>(null);
  const [height, setHeight] = useState(0);

  useEffect(() => {
    if (contentRef.current) {
      setHeight(contentRef.current.scrollHeight);
    }
  }, []);

  return (
    <div className={`faq-item ${open ? "open" : ""}`}>
      <button className="faq-question" onClick={() => setOpen((o) => !o)}>
        <span>{q}</span>
        <span className="faq-icon">{open ? "−" : "+"}</span>
      </button>
      <div
        className="faq-answer"
        style={{ maxHeight: open ? `${height}px` : "0px" }}>
        <div ref={contentRef}>
          <p>{a}</p>
        </div>
      </div>
    </div>
  );
}
