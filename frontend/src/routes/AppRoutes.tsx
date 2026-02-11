import { Suspense, lazy, useEffect } from "react";
import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";
import { startLoading, stopLoading } from "../utils/globalLoader";
import HomePage from "../pages/HomePage";

const AboutPage = lazy(() => import("../pages/AboutPage"));
const CoursesPage = lazy(() => import("../pages/CoursesPage"));
const GradeDetailPage = lazy(() => import("../pages/GradeDetailPage"));
const CustomGradePage = lazy(() => import("../pages/CustomGradePage"));
const MyOrdersPage = lazy(() => import("../pages/MyOrdersPage"));
const MyOrderDetailPage = lazy(() => import("../pages/MyOrderDetailPage"));
const CheckoutPage = lazy(() => import("../pages/CheckoutPage"));
const PrivacyPolicyPage = lazy(() => import("../pages/PrivacyPolicyPage"));
const TermsOfUsePage = lazy(() => import("../pages/TermsOfUsePage"));

function ScrollToTopOnRouteChange() {
  const { pathname, hash } = useLocation();

  useEffect(() => {
    
    if (hash) return;
    startLoading();
    window.scrollTo({ top: 0, behavior: "smooth" });
    setTimeout(stopLoading, 150);
  }, [pathname, hash]);

  return null;
}

export function AppRoutes() {
  return (
    <BrowserRouter>
      <ScrollToTopOnRouteChange />
      <Suspense fallback={<main className="container">Carregando...</main>}>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/quem-somos" element={<AboutPage />} />
          <Route path="/cursos" element={<CoursesPage />} />
          <Route path="/grades/:slug" element={<GradeDetailPage />} />
          <Route path="/monte-sua-grade" element={<CustomGradePage />} />
          <Route path="/meus-pedidos" element={<MyOrdersPage />} />
          <Route path="/meus-pedidos/:id" element={<MyOrderDetailPage />} />
          <Route path="/checkout/:id_pedido" element={<CheckoutPage />} />
          <Route
            path="/politica-de-privacidade"
            element={<PrivacyPolicyPage />}
          />
          <Route path="/termos-de-uso" element={<TermsOfUsePage />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  );
}
