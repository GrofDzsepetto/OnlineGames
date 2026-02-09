import { GoogleLogin } from "@react-oauth/google";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";

const Login = () => {
  const navigate = useNavigate();
  const { refreshUser } = useAuth();

  return (
   <GoogleLogin
  onSuccess={async (cred) => {
    //Log if Needed
    //console.log("Google success", cred);

    if (!cred.credential) {
      console.log("No credential");
      return;
    }

    const res = await fetch("https://dzsepetto.hu/api/auth/google.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ token: cred.credential }),
    });

    //console.log("google.php status:", res.status);

const text = await res.text();
console.log("RAW google.php response:", text);
    await refreshUser();
    navigate("/");
  }}
/>

  );
};

export default Login;
