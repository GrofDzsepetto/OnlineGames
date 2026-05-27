import { GoogleLogin } from "@react-oauth/google";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";
import { API_BASE } from "../config/api";
import { motion } from "framer-motion";
import { useTranslation } from "react-i18next";
import "../styles/login.css";

export default function Login() {
  const navigate = useNavigate();
  const { refreshUser } = useAuth();
  const { t } = useTranslation();

  return (
    <div className="login-page">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="login-container"
      >
        <div className="login-card">
          <div className="login-header">
            <h1 className="login-title">{t("login.title")}</h1>
            <p className="login-subtitle">{t("login.subtitle")}</p>
          </div>

          <div className="login-button-wrapper">
            <div className="login-button-scale">
              <GoogleLogin
                theme="filled_black"
                shape="pill"
                size="large"
                onSuccess={async (cred) => {
                  try {
                    if (!cred.credential) {
                      console.error("Missing Google credential");
                      return;
                    }

                    const res = await fetch(`${API_BASE}/auth/google.php`, {
                      method: "POST",
                      headers: {
                        "Content-Type": "application/json",
                      },
                      credentials: "include",
                      body: JSON.stringify({
                        token: cred.credential,
                      }),
                    });

                    const data = await res.json();

                    if (!res.ok || !data?.success) {
                      console.error("Google login failed:", data);
                      return;
                    }

                    const loggedInUser = await refreshUser();

                    if (!loggedInUser) {
                      console.error("Login sikeres volt, de a user.php nem adott vissza usert.");
                      return;
                    }

                    navigate("/");
                  } catch (error) {
                    console.error("Google login error:", error);
                  }
                }}
                onError={() => {
                  console.error("Google login failed");
                }}
              />
            </div>
          </div>

          <div className="login-terms">{t("login.terms")}</div>
        </div>

        <div className="login-footer">
          © {new Date().getFullYear()} {t("login.footer")}
        </div>
      </motion.div>
    </div>
  );
}