import "./GoogleReviews.css";

export default function GoogleReviews() {
  return (
    <section className="google-reviews-section">
      <div className="container">
        <div className="reviews-header"></div>

        <div className="reviews-content">
          {
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3731.6739290408086!2d-46.61066972475167!3d-20.72346148085057!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94b6c3740a343633%3A0xff6b720a607cb6bf!2sIntelecto%20Cursos%20Profissionalizantes!5e0!3m2!1spt-BR!2sbr!4v1769626940091!5m2!1spt-BR!2sbr"
              width={600}
              height={450}
              style={{ border: "0" }}
              allowFullScreen
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"></iframe>
          }
        </div>
      </div>
    </section>
  );
}
