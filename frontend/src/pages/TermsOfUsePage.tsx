import Header from "../components/Layout/Header";
import Footer from "../components/Layout/Footer";
import "./LegalPage.css";

export default function TermsOfUsePage() {
  return (
    <>
      <Header />
      <main className="legal-page">
        <section className="legal-hero">
          <div className="container">
            <h1>Termos de Uso</h1>
            <p>
              Condições para uso do site e contratação dos serviços
              educacionais.
            </p>
            <div className="legal-meta">
              Atualizado em 02 de fevereiro de 2026
            </div>
          </div>
        </section>

        <section className="legal-content">
          <div className="container">
            <div className="legal-section">
              <h2>Dados do fornecedor</h2>
              <ul>
                <li>Razão Social: Miguel Campos Lamonier ME.</li>
                <li>
                  Nome fantasia: Escola Intelecto Profissionalizantes e Idiomas.
                </li>
                <li>CNPJ: 30.258.413/0001-31.</li>
                <li>
                  Endereço: R. Dr. Manoel Patti, 20 - Centro, Passos - MG,
                  37900-053, Brasil.
                </li>
                <li>Site: www.cursosintelecto.com.br.</li>
                <li>E-mail: intelectoprofissionalizantes@gmail.com.</li>
                <li>WhatsApp: (35) 99842-1176.</li>
                <li>Última atualização: 02/02/2026.</li>
              </ul>
            </div>

            <div className="legal-section">
              <h2>1) Aceitação</h2>
              <p>
                Ao acessar este site e/ou contratar nossos cursos e serviços,
                você declara que leu, compreendeu e concorda com estes Termos de
                Uso e com a Política de Privacidade.
              </p>
            </div>

            <div className="legal-section">
              <h2>2) Transparência do fornecedor e da oferta</h2>
              <p>
                Nos meios eletrônicos de oferta e contratação, disponibilizamos
                em local de destaque e fácil visualização: identificação do
                fornecedor (incluindo CNPJ), endereço físico e eletrônico e
                canais de contato.
              </p>
              <p>
                Também informamos de forma clara as condições integrais da
                oferta, incluindo modalidades de pagamento, disponibilidade,
                forma e prazo de execução do serviço, além de eventuais
                restrições.
              </p>
            </div>

            <div className="legal-section">
              <h2>3) Cadastro e conta</h2>
              <p>
                Você é responsável por fornecer informações corretas e
                atualizadas. O acesso à conta é pessoal e intransferível;
                mantenha login e senha sob sigilo e responda pelas atividades
                realizadas em sua conta.
              </p>
            </div>

            <div className="legal-section">
              <h2>4) Contratação, confirmações e sumário do contrato</h2>
              <p>
                Antes de finalizar a contratação, apresentamos um sumário do
                contrato com as informações necessárias ao seu direito de
                escolha, com destaque para cláusulas que limitem direitos,
                quando aplicável.
              </p>
              <p>
                Após a contratação, confirmamos imediatamente o recebimento da
                aceitação da oferta e disponibilizamos o contrato ou termo em
                meio que permita sua conservação e reprodução.
              </p>
            </div>

            <div className="legal-section">
              <h2>5) Liberação de acesso (forma e prazo do serviço)</h2>
              <p>
                A liberação de acesso às aulas e plataforma ocorrerá após a
                confirmação do pagamento.
              </p>
              <p>
                Aceitamos meios de pagamento disponibilizados no momento da
                compra (como Pix, cartão e boleto, quando oferecidos na tela de
                checkout), e o prazo de confirmação pode variar conforme o meio
                escolhido e as regras do intermediador ou financeira.
              </p>
              <p>
                Enquanto o pagamento estiver pendente, o acesso poderá não ser
                liberado ou poderá ficar restrito até a confirmação.
              </p>
            </div>

            <div className="legal-section">
              <h2>6) Pagamentos</h2>
              <p>
                Valores, planos e condições de pagamento são exibidos no momento
                da contratação, matrícula e/ou no contrato. Pagamentos podem ser
                processados por terceiros especializados, e utilizamos
                mecanismos de segurança no tratamento de dados e nas operações
                de pagamento.
              </p>
            </div>

            <div className="legal-section">
              <h2>7) Inadimplência (atraso)</h2>
              <p>
                O não pagamento das parcelas no vencimento caracteriza
                inadimplemento. Em caso de atraso, podem incidir encargos e
                custos administrativos previstos em contrato e na legislação
                aplicável.
              </p>
              <p>
                A inadimplência pode acarretar suspensão temporária do acesso a
                serviços (plataforma, aulas e recursos) até a regularização,
                além de outras medidas cabíveis.
              </p>
            </div>

            <div className="legal-section">
              <h2>8) Cancelamento, desistência e reembolsos</h2>
              <p>
                As regras de cancelamento e reembolso seguem o contrato ou termo
                de matrícula e a legislação vigente. O pedido de cancelamento
                deve ser formalizado por escrito (por e-mail ou WhatsApp
                oficiais).
              </p>
              <p>
                A desistência não formalizada não isenta obrigações já assumidas
                e vencidas.
              </p>
            </div>

            <div className="legal-section">
              <h2>9) Direito de arrependimento (contratação à distância)</h2>
              <p>
                Informamos de forma clara e ostensiva os meios adequados e
                eficazes para o exercício do direito de arrependimento.
              </p>
              <p>
                Você poderá exercer o arrependimento pela mesma ferramenta
                utilizada na contratação (ex.: WhatsApp ou e-mail), e enviaremos
                confirmação imediata do recebimento da manifestação.
              </p>
              <p>
                Quando aplicável, comunicaremos imediatamente o arrependimento à
                instituição financeira ou administradora do cartão para evitar
                lançamento na fatura ou efetivar estorno.
              </p>
            </div>

            <div className="legal-section">
              <h2>10) Atendimento ao consumidor</h2>
              <p>
                Mantemos serviço adequado e eficaz de atendimento em meio
                eletrônico para demandas de informação, dúvida, reclamação,
                suspensão ou cancelamento.
              </p>
              <p>
                Confirmamos imediatamente o recebimento das demandas e
                encaminhamos nossa manifestação ao consumidor em até 5 dias.
              </p>
            </div>

            <div className="legal-section">
              <h2>11) Propriedade intelectual e uso do conteúdo</h2>
              <p>
                Os conteúdos, materiais e plataformas são de uso exclusivo do
                aluno para fins educacionais. É proibido reproduzir, gravar,
                distribuir, compartilhar ou comercializar conteúdos sem
                autorização prévia e expressa.
              </p>
            </div>

            <div className="legal-section">
              <h2>12) Uso permitido e condutas proibidas</h2>
              <p>
                É proibido utilizar o site ou plataforma para fins ilícitos,
                tentar acessar áreas restritas sem autorização ou praticar atos
                que prejudiquem segurança e disponibilidade do serviço. Podemos
                suspender ou bloquear acessos em caso de violação destes Termos
                ou risco à segurança.
              </p>
            </div>

            <div className="legal-section">
              <h2>13) Comunicações eletrônicas</h2>
              <p>
                Você concorda em receber comunicações por meios eletrônicos
                (e-mail, WhatsApp e outros canais digitais) relacionadas à
                matrícula, aulas, pagamentos e suporte.
              </p>
            </div>

            <div className="legal-section">
              <h2>14) Caso fortuito/força maior e disponibilidade</h2>
              <p>
                Podem ocorrer indisponibilidades temporárias por manutenção,
                atualizações, falhas técnicas ou eventos externos. Não nos
                responsabilizamos por atrasos ou suspensões decorrentes de caso
                fortuito e força maior, na medida permitida pela legislação.
              </p>
            </div>

            <div className="legal-section">
              <h2>15) Alterações destes Termos</h2>
              <p>
                Podemos atualizar estes Termos de Uso a qualquer momento. A
                versão vigente estará sempre disponível nesta página, com data
                de atualização.
              </p>
            </div>

            <div className="legal-section">
              <h2>16) Foro</h2>
              <p>
                Quando permitido pela legislação aplicável, fica eleito o foro
                da Comarca de Passos/MG para dirimir eventuais controvérsias.
              </p>
            </div>

            <div className="legal-section">
              <h2>17) Contato</h2>
              <div className="legal-contact">
                <p>
                  <strong>E-mail:</strong>{" "}
                  intelectoprofissionalizantes@gmail.com.
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
