import { GoogleLogin } from "@react-oauth/google";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";
import { API_BASE } from "../config/api";

const Login = () => {
  const navigate = useNavigate();
  const { refreshUser } = useAuth();

  return (
    <GoogleLogin
      onSuccess={async (cred) => {
        if (!cred.credential) {
          console.log("No credential");
          return;
        }

        const res = await fetch(`${API_BASE}/auth/google.php`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "include",
          body: JSON.stringify({ token: cred.credential }),
        });

        const text = await res.text();
        console.log("RAW google.php response:", text);

        await refreshUser();
        navigate("/");
      }}
    />
  );
};

export default Login;
