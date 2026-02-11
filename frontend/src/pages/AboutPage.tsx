import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import GoogleReviews from "../components/GoogleReviews/GoogleReviews";
import "./AboutPage.css";

export default function AboutPage() {
  return (
    <>
      <Header />
      <main className="about-page">
        <div className="hero-about">
          <div className="container hero-content">
            <h1>Intelecto Profissionalizantes e Idiomas</h1>
            <p className="lead">Conhecimento que vira oportunidade.</p>
          </div>
        </div>

        <section className="about-section">
          <div className="container">
            <h2>QUEM SOMOS</h2>
            <p>
              A Escola Intelecto foi criada em 2018, na cidade de Passos (MG).
              Fundada por Washington Lamonier e seu filho, Miguel Campos
              Lamonier, a instituição nasceu com o propósito de transformar
              conhecimento em oportunidade.
            </p>
            <p>
              Em 2022, a Escola Intelecto consolidou sua identidade e ampliou
              sua atuação como Intelecto Profissionalizantes e Idiomas,
              oferecendo cursos livres com foco no desenvolvimento de
              habilidades práticas e teóricas, por meio de uma metodologia
              própria, atualizada e alinhada às exigências do mercado de
              trabalho.
            </p>
            <p>
              Nosso compromisso é apoiar o crescimento profissional e pessoal de
              cada aluno, promovendo mais autonomia, empregabilidade e
              desenvolvimento, com responsabilidade social e foco em resultados.
            </p>
          </div>
        </section>

        <section className="about-section alt">
          <div className="container">
            <h2>NOSSO SISTEMA DE ENSINO</h2>
            <p>
              Na Intelecto Profissionalizantes e Idiomas, nosso sistema de
              ensino foi pensado para quem busca crescimento pessoal e
              profissional de verdade.
            </p>
            <p>
              Contamos com uma equipe dedicada ao desenvolvimento do nosso
              material didático, produzido por profissionais que atuam na área e
              conhecem as exigências do mercado. Além disso, oferecemos
              acompanhamento individual e suporte com professores presentes em
              sala, garantindo aprendizado mais próximo e eficiente.
            </p>
            <p>
              Nossa metodologia valoriza a formação empreendedora, promove aulas
              interdisciplinares e conecta teoria e prática para transformar
              conhecimento em resultados, ajudando você a evoluir na carreira e
              na vida.
            </p>
          </div>
        </section>

        <section className="about-section">
          <div className="container">
            <h2>NOSSOS VALORES</h2>
            <div className="values-grid">
              <div className="value-card">
                <h3>Excelência</h3>
                <p>
                  Entregamos qualidade em cada etapa: do atendimento ao aluno ao
                  conteúdo em sala, com foco em resultados reais.
                </p>
              </div>
              <div className="value-card">
                <h3>Inovação</h3>
                <p>
                  Buscamos continuamente novas metodologias e tecnologias para
                  tornar o aprendizado mais atual, prático e eficiente.
                </p>
              </div>
              <div className="value-card">
                <h3>Inclusão</h3>
                <p>
                  Acreditamos que educação é para todos. Criamos um ambiente
                  acolhedor, com respeito, acessibilidade e oportunidades sem
                  discriminação.
                </p>
              </div>
              <div className="value-card">
                <h3>Impacto Social</h3>
                <p>
                  Transformamos vidas por meio da educação, contribuindo para o
                  desenvolvimento profissional e para o fortalecimento da nossa
                  comunidade.
                </p>
              </div>
            </div>
          </div>
        </section>

        <GoogleReviews />
      </main>
      <Footer />
    </>
  );
}
