import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import "./LegalPage.css";

export default function PrivacyPolicyPage() {
  return (
    <>
      <Header />
      <main className="legal-page">
        <section className="legal-hero">
          <div className="container">
            <h1>Política de Privacidade (LGPD)</h1>
            <p>
              Como coletamos, usamos, compartilhamos e protegemos seus dados.
            </p>
            <div className="legal-meta">
              Atualizado em 02 de fevereiro de 2026
            </div>
          </div>
        </section>

        <section className="legal-content">
          <div className="container">
            <div className="legal-section">
              <h2>Quem somos?</h2>
              <p>
                A Intelecto Profissionalizantes e Idiomas oferece cursos e
                serviços educacionais. Esta política explica como tratamos dados
                pessoais em nossos canais digitais e no processo de matrícula,
                conforme a LGPD (Lei nº 13.709/2018).
              </p>
            </div>

            <div className="legal-section">
              <h2>Quais dados vocês coletam?</h2>
              <ul>
                <li>
                  Dados de identificação e contato (nome, e-mail, telefone e
                  WhatsApp).
                </li>
                <li>
                  Dados cadastrais e documentos quando necessários (ex.: CPF).
                </li>
                <li>
                  Informações de matrícula (curso, turma, plano e solicitações).
                </li>
                <li>Dados de navegação (IP, dispositivo e cookies).</li>
                <li>
                  Informações relacionadas a pagamentos processadas por
                  intermediadores financeiros (sem guardar dados completos de
                  cartão).
                </li>
              </ul>
            </div>

            <div className="legal-section">
              <h2>Por que vocês coletam meus dados (finalidades)?</h2>
              <ul>
                <li>
                  Viabilizar matrícula, contrato, acesso ao curso e pagamentos.
                </li>
                <li>Prestar suporte e atendimento.</li>
                <li>Enviar comunicados relacionados ao serviço.</li>
                <li>Cumprir obrigações legais e regulatórias.</li>
                <li>Melhorar a experiência e a segurança do site.</li>
              </ul>
            </div>

            <div className="legal-section">
              <h2>Qual é a base legal para o tratamento?</h2>
              <p>
                Tratamos dados principalmente para execução de contrato
                (matrícula e serviços), por legítimo interesse (ex.: suporte e
                melhorias) e para cumprimento de obrigação legal e regulatória.
                Quando necessário, também podemos tratar mediante consentimento.
              </p>
            </div>

            <div className="legal-section">
              <h2>Vocês armazenam dados do meu cartão?</h2>
              <p>
                Não. Os dados de pagamento são tratados por intermediadores
                financeiros, e não armazenamos os dados completos do cartão.
              </p>
            </div>

            <div className="legal-section">
              <h2>Com quem vocês compartilham meus dados?</h2>
              <p>
                Compartilhamos apenas quando necessário para executar os
                serviços, como:
              </p>
              <ul>
                <li>Processadores de pagamento.</li>
                <li>
                  Fornecedores de tecnologia (hospedagem, sistemas e
                  mensageria).
                </li>
                <li>Autoridades, quando houver obrigação legal.</li>
              </ul>
            </div>

            <div className="legal-section">
              <h2>Por quanto tempo vocês guardam meus dados?</h2>
              <p>
                Mantemos os dados pelo tempo necessário para cumprir as
                finalidades desta política e atender exigências legais e
                contratuais.
              </p>
            </div>

            <div className="legal-section">
              <h2>Como vocês protegem meus dados?</h2>
              <p>
                Adotamos medidas técnicas e organizacionais para proteger os
                dados contra acessos não autorizados, perdas e alterações
                indevidas, reforçando a transparência sobre o tratamento.
              </p>
            </div>

            <div className="legal-section">
              <h2>Quais são meus direitos como titular de dados?</h2>
              <ul>
                <li>Acesso aos dados.</li>
                <li>Correção e atualização.</li>
                <li>Informações sobre finalidade e compartilhamento.</li>
                <li>Portabilidade e oposição, quando cabível.</li>
                <li>
                  Revogação de consentimento e exclusão, quando aplicável.
                </li>
              </ul>
            </div>

            <div className="legal-section">
              <h2>Vocês usam cookies? Para quê?</h2>
              <p>
                Sim. Usamos cookies para manter sua sessão ativa e melhorar a
                navegação.
              </p>
            </div>

            <div className="legal-section">
              <h2>Posso desativar cookies?</h2>
              <p>
                Sim. Você pode gerenciar cookies no seu navegador, mas algumas
                funcionalidades do site podem não funcionar corretamente sem
                eles.
              </p>
            </div>

            <div className="legal-section">
              <h2>Vocês podem falar comigo por e-mail e WhatsApp?</h2>
              <p>
                Sim. Podemos usar e-mail, WhatsApp e outros canais para
                comunicações relacionadas à matrícula, contrato, pagamentos e
                suporte.
              </p>
            </div>

            <div className="legal-section">
              <h2>
                Como entrar em contato sobre privacidade e dados pessoais?
              </h2>
              <div className="legal-contact">
                <p>
                  Para dúvidas ou solicitações, fale com nossa equipe:
                  <br />
                  <strong>WhatsApp:</strong>{" "}
                  <a
                    href="https://wa.me/5535998421176"
                    target="_blank"
                    rel="noreferrer">
                    (35) 99842-1176
                  </a>
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}
