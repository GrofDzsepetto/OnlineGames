import { createContext, useContext, useEffect, useState } from "react";
import { API_BASE } from "../config/api";

type User = {
  id: string;
  email: string;
  name: string;
};

type AuthContextType = {
  user: User | null;
  refreshUser: () => Promise<User | null>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextType | null>(null);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);

  const refreshUser = async (): Promise<User | null> => {
    try {
      const res = await fetch(`${API_BASE}/auth/user.php`, {
        credentials: "include",
      });

      const data = await res.json();

      console.log("USER.PHP RESPONSE:", data);

      if (!res.ok || !data?.success || !data?.data?.user) {
        setUser(null);
        return null;
      }

      const apiUser = data.data.user;

      const normalizedUser: User = {
        id: String(apiUser.id),
        email: String(apiUser.email),
        name: apiUser.name ?? "",
      };

      setUser(normalizedUser);
      return normalizedUser;
    } catch (error) {
      console.error("Hiba a felhasználó lekérése során:", error);
      setUser(null);
      return null;
    }
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

  if (!ctx) {
    throw new Error("useAuth must be used inside AuthProvider");
  }

  return ctx;
};