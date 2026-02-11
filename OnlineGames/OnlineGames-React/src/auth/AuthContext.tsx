import { createContext, useContext, useEffect, useState } from "react";
import { API_BASE } from "../config/api";

type User = {
  id: string;
  email: string;
  name: string;
};

type AuthContextType = {
  user: User | null;
  refreshUser: () => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextType | null>(null);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);

const refreshUser = async () => {
  const res = await fetch(`${API_BASE}/auth/user.php`, {
    credentials: "include",
    
  });

  const text = await res.text();
  console.log("RAW user.php response:", text);
  console.log("API_BASE =", API_BASE);
  let data;
  try {
    data = JSON.parse(text);
  } catch {
    setUser(null);
    return;
  }

  if (!data?.user) {
    setUser(null);
    return;
  }

  setUser({
    id: String(data.user.id),
    email: String(data.user.email),
    name: data.user.name ?? "",
  });
};


  const logout = async () => {
    try {
      await fetch(`${API_BASE}/auth/logout.php`, {
        method: "POST",
        credentials: "include",
      });
    } catch (error) {
      console.error("Hiba a kijelentkezés során:", error);
    } finally {
      setUser(null);
    }
  };

  useEffect(() => {
    refreshUser();
  }, []);

  return (
    <AuthContext.Provider value={{ user, refreshUser, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
  return ctx;
};
