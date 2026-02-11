import React from "react";
import ReactDOM from "react-dom/client";
import { RouterProvider } from "react-router-dom";
import { router } from "./router/AppRouter";
import { GoogleOAuthProvider } from "@react-oauth/google";
import { AuthProvider } from "./auth/AuthContext";

import "./i18n";

import "./styles/globals.css";
import "./styles/layout.css";
import "./styles/buttons.css";
import "./styles/cards.css";
import "./styles/Quiz/quiz.css"
import "./styles/Quiz/createQuiz.css"
import "./styles/Quiz/quizList.css"

const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID ?? "";
if (!CLIENT_ID) {
  console.error("Missing VITE_GOOGLE_CLIENT_ID in .env");
}

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <GoogleOAuthProvider clientId={CLIENT_ID}>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </GoogleOAuthProvider>
  </React.StrictMode>
);
